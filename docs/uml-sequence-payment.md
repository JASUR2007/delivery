# UML Sequence Diagram: Проведение платежа (Payment Workflow)

Диаграмма последовательности отображает процесс регистрации платежа бухгалтером с 5-ступенчатой последовательной валидацией, пессимистической блокировкой строк на уровне базы данных (`lockForUpdate`) и автоматическим пересчетом остатков этапов и сметы.

---

## Диаграмма последовательности (Mermaid)

```mermaid
sequenceDiagram
    autonumber
    actor Buxgalter as Бухгалтер
    participant UI as Inertia / React UI
    participant Ctrl as DashboardController
    participant Req as CreatePaymentRequest
    participant Svc as AccountingService
    participant DB as База данных (SQLite/PostgreSQL)
    participant Calc as MoneyCalculator (bcmath)

    Buxgalter->>UI: Заполняет форму оплаты (Этап, Сумма, Дата, Смета)
    UI->>Ctrl: POST /payments (payment_milestone_id, budget_item_id, amount, payment_date)
    Ctrl->>Req: Валидация входных типов данных
    Req-->>Ctrl: Валидированные параметры (CreatePaymentDTO)

    Ctrl->>Svc: registerPayment(dto)
    
    activate Svc
    Svc->>DB: DB::transaction(callback)
    activate DB
    
    Note over Svc,DB: Шаг 1: Пессимистическая блокировка данных
    Svc->>DB: Milestone::lockForUpdate()->find(id)
    DB-->>Svc: Заблокированная запись этапа договора
    Svc->>DB: BudgetItem::lockForUpdate()->find(id)
    DB-->>Svc: Заблокированная запись строки сметы

    Note over Svc: Шаг 2: Последовательные бизнес-проверки
    
    rect rgb(240, 248, 255)
        Note over Svc: Проверка 1: Статус договора
        opt Договор НЕ Active и НЕ Completed
            Svc-->>Ctrl: Исключение: Shartnoma faol emas
            Ctrl-->>UI: Ошибка 422: Shartnoma faol emas
        end

        Note over Svc: Проверка 2: Условие этапа
        opt Условие этапа не выполнено (например, товар не поставлен)
            Svc-->>Ctrl: Исключение: Bosqich to‘lov sharti bajarilmagan
            Ctrl-->>UI: Ошибка 422: Bosqich to‘lov sharti bajarilmagan
        end

        Note over Svc: Проверка 3: Оплата предыдущего этапа
        opt Предыдущий по порядку этап не оплачен полностью
            Svc-->>Ctrl: Исключение: Oldingi to‘lov bosqichi to‘liq to‘lanmagan
            Ctrl-->>UI: Ошибка 422: Oldingi to‘lov bosqichi to‘liq to‘lanmagan
        end

        Note over Svc: Проверка 4: Достаточность остатка этапа
        Svc->>Calc: greaterThan(amount, milestone.remainingBalance)
        opt Сумма больше остатка этапа или <= 0
            Svc-->>Ctrl: Исключение: To‘lov summasi bosqich qoldig‘idan ko‘p
            Ctrl-->>UI: Ошибка 422: To‘lov summasi bosqich qoldig‘idan ko‘p
        end

        Note over Svc: Проверка 5: Год сметы и остаток месяца
        Svc->>Calc: greaterThan(amount, budgetItem.remainingBalance)
        opt Дата не совпадает с годом сметы ИЛИ сумма > остатка сметы
            Svc-->>Ctrl: Исключение: Smeta qoldig‘i yetarli emas
            Ctrl-->>UI: Ошибка 422: Smeta qoldig‘i yetarli emas
        end
    end

    Note over Svc,DB: Шаг 3: Сохранение платежа и списание
    Svc->>DB: Payment::create(status=COMPLETED)
    DB-->>Svc: Запись создана
    
    Svc->>Calc: sub(milestone.remaining, amount)
    Svc->>Calc: sub(budget.remaining, amount)
    
    Svc->>DB: Обновление статуса этапа (PartiallyPaid / Paid)
    Svc->>DB: Commit транзакции (Снятие блокировок)
    deactivate DB
    
    Svc-->>Ctrl: Успешно проведенный платеж (Payment)
    deactivate Svc
    
    Ctrl-->>UI: Redirect back с сообщением об успехе
    UI-->>Buxgalter: Обновленная таблица оплат и актуальные остатки
```

---

## Ключевые гарантии процесса
1. **Атомарность:** Все операции происходят внутри `DB::transaction()`. В случае ошибки на любой из 5 проверок транзакция полностью откатывается.
2. **Защита от Race Condition:** Строки этапа и сметы блокируются через `lockForUpdate()`, исключая двойное списание средств при одновременных запросах.
3. **Строгая последовательность:** Нарушение хотя бы одного из 5 правил немедленно прерывает выполнение на первом сработавшем условии.
