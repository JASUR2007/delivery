# UML Class Diagram (Диаграмма классов)

Диаграмма классов отображает ключевые доменные модели, сервисы, перечисления (Enums) и объекты передачи данных (DTO), а также связи между ними.

---

## Диаграмма классов доменной модели (Mermaid)

```mermaid
classDiagram
    %% Enums
    class UserRole {
        <<enumeration>>
        EXECUTOR
        SUPERVISOR
        ACCOUNTANT
    }

    class ContractStatus {
        <<enumeration>>
        DRAFT
        ACTIVE
        COMPLETED
    }

    class PenaltyStatus {
        <<enumeration>>
        PENDING
        APPROVED
        REJECTED
    }

    class MilestoneStatus {
        <<enumeration>>
        PLANNED
        READY_TO_PAY
        PARTIALLY_PAID
        PAID
        OVERDUE
    }

    class MilestoneCondition {
        <<enumeration>>
        CONTRACT_ACTIVATED
        GOODS_FULLY_DELIVERED
    }

    %% Models
    class User {
        +int id
        +string name
        +string email
        +UserRole role
        +isExecutor() bool
        +isSupervisor() bool
        +isAccountant() bool
    }

    class Contract {
        +int id
        +string contract_number
        +string supplier_name
        +Carbon start_date
        +int duration_days
        +Carbon deadline_date
        +ContractStatus status
        +int created_by
        +getTotalPaidAmount() string
        +getDeliveredPaidQuantity() int
        +getRemainingPaidQuantity() int
        +arePaidGoodsFullyDelivered() bool
    }

    class ContractItem {
        +int id
        +int contract_id
        +string item_name
        +int paid_quantity
        +int bonus_quantity
        +string unit_price
        +getDeliveredPaidQuantity() int
        +getRemainingPaidQuantity() int
    }

    class DeliveryBatch {
        +int id
        +int contract_id
        +Carbon delivery_date
        +string notes
        +int created_by
    }

    class DeliveryBatchItem {
        +int id
        +int delivery_batch_id
        +int contract_item_id
        +int delivered_paid_quantity
        +int delivered_bonus_quantity
    }

    class Penalty {
        +int id
        +int contract_id
        +Carbon calculation_date
        +int delay_days
        +string calculated_amount
        +string approved_amount
        +string cap_amount
        +PenaltyStatus status
        +int reviewed_by
        +Carbon reviewed_at
    }

    class BudgetItem {
        +int id
        +int financial_year
        +string cost_item
        +int month
        +string allocated_amount
        +getSpentAmount() string
        +getRemainingBalance() string
    }

    class PaymentMilestone {
        +int id
        +int contract_id
        +int step_order
        +string name
        +string percentage
        +string amount
        +MilestoneCondition condition_type
        +Carbon due_date
        +getPaidAmount() string
        +getRemainingBalance() string
        +calculateStatus(asOfDate) MilestoneStatus
    }

    class Payment {
        +int id
        +int payment_milestone_id
        +int budget_item_id
        +string amount
        +Carbon payment_date
        +string payment_number
        +bool is_voided
        +string void_reason
        +int created_by
        +int voided_by
    }

    %% Services
    class MoneyCalculator {
        +add(a, b) string
        +sub(a, b) string
        +mul(a, b) string
        +div(a, b) string
        +format(val) string
        +compare(a, b) int
    }

    class PenaltyCalculatorService {
        -MoneyCalculator calculator
        +calculate(contract, asOfDate) PenaltyCalculationDTO
    }

    class AccountingService {
        -MoneyCalculator calculator
        +registerPayment(dto) Payment
        +voidPayment(dto) Payment
    }

    %% Relationships
    User "1" --> "*" Contract : creates
    User "1" --> "*" DeliveryBatch : registers
    User "1" --> "*" Penalty : reviews
    User "1" --> "*" Payment : creates/voids

    Contract "1" *-- "1..*" ContractItem : contains
    Contract "1" *-- "*" DeliveryBatch : receives
    Contract "1" *-- "*" Penalty : calculates
    Contract "1" *-- "2" PaymentMilestone : defines

    DeliveryBatch "1" *-- "1..*" DeliveryBatchItem : contains
    ContractItem "1" --> "*" DeliveryBatchItem : references

    PaymentMilestone "1" *-- "*" Payment : receives
    BudgetItem "1" --> "*" Payment : funds

    PenaltyCalculatorService ..> MoneyCalculator : uses
    AccountingService ..> MoneyCalculator : uses
    AccountingService ..> Payment : manages
    AccountingService ..> PaymentMilestone : updates
    AccountingService ..> BudgetItem : updates
```

---

## Архитектурные слои
1. **Models & Enums:** Строго типизированные сущности Eloquent, инкапсулирующие расчетные свойства (`getTotalPaidAmount`, `getRemainingBalance`, `arePaidGoodsFullyDelivered`).
2. **DTO (Data Transfer Objects):** Гарантия чистоты входных данных между контроллерами и сервисами без утечки сырых HTTP-массивов.
3. **Services:** Изолированная бизнес-логика (расчет неустойки, 5-ступенчатая валидация оплат, пессимистические блокировки).
4. **MoneyCalculator:** Базовый математический компонент на базе `bcmath`, защищающий систему от ошибок точности чисел с плавающей точкой (`float`).
