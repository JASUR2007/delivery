# Ma’lumotlar bazasi strukturasi (ERD - Entity Relationship Diagram)

Quyida tizimdagi jadvallar, ularning maydonlari va o‘zaro munosabatlari aks ettirilgan.

```mermaid
erDiagram
    users ||--o{ contracts : "created_by"
    users ||--o{ delivery_batches : "created_by"
    users ||--o{ penalties : "reviewed_by"
    users ||--o{ payments : "created_by"
    users ||--o{ payments : "voided_by"

    contracts ||--|{ contract_items : "contains"
    contracts ||--o{ delivery_batches : "receives"
    contracts ||--|{ payment_milestones : "schedules"
    contracts ||--o{ penalties : "incurs"
    contracts ||--o{ payments : "paid_under"

    contract_items ||--o{ delivery_batch_items : "delivered_in"
    delivery_batches ||--|{ delivery_batch_items : "includes"

    payment_milestones ||--o{ payments : "settled_by"
    budget_items ||--o{ payments : "funded_from"

    users {
        bigint id PK
        string name
        string email UK
        string password
        string role "executor | supervisor | accountant"
        timestamp created_at
        timestamp updated_at
    }

    contracts {
        bigint id PK
        string contract_number UK
        string supplier_name
        date start_date
        integer duration_days
        date deadline_date
        string status "draft | active | completed"
        bigint created_by FK
        timestamp created_at
        timestamp updated_at
    }

    contract_items {
        bigint id PK
        bigint contract_id FK
        string item_name
        integer paid_quantity
        integer bonus_quantity
        decimal unit_price
        timestamp created_at
        timestamp updated_at
    }

    delivery_batches {
        bigint id PK
        bigint contract_id FK
        date delivery_date
        string notes
        bigint created_by FK
        timestamp created_at
        timestamp updated_at
    }

    delivery_batch_items {
        bigint id PK
        bigint delivery_batch_id FK
        bigint contract_item_id FK
        integer delivered_paid_quantity
        integer delivered_bonus_quantity
        timestamp created_at
        timestamp updated_at
    }

    penalties {
        bigint id PK
        bigint contract_id FK
        date calculation_date
        integer delay_days
        decimal calculated_amount
        decimal cap_amount
        string status "pending | approved | rejected"
        decimal approved_amount
        text supervisor_notes
        bigint reviewed_by FK
        timestamp reviewed_at
        json breakdown
        timestamp created_at
        timestamp updated_at
    }

    payment_milestones {
        bigint id PK
        bigint contract_id FK
        smallint step_order
        string name
        decimal percentage
        decimal amount
        string condition_type "contract_activated | goods_fully_delivered"
        date due_date
        timestamp created_at
        timestamp updated_at
    }

    budget_items {
        bigint id PK
        smallint financial_year
        string cost_item
        tinyint month "1 to 12"
        decimal allocated_amount
        string notes
        timestamp created_at
        timestamp updated_at
    }

    payments {
        bigint id PK
        string payment_number
        bigint contract_id FK
        bigint milestone_id FK
        bigint budget_item_id FK
        decimal amount
        date payment_date
        string status "active | voided"
        bigint created_by FK
        bigint voided_by FK
        timestamp voided_at
        text void_reason
        timestamp created_at
        timestamp updated_at
    }
```

---

## Jadvallar tavsifi

1. **`users`**: Tizim foydalanuvchilari. `role` ustuni (`executor`, `supervisor`, `accountant`) orqali tizimdagi huquqlar belgilanadi.
2. **`contracts`**: Shartnomaning asosiy kartochkasi. Holatlari: `draft` (Qoralama), `active` (Faol), `completed` (Yakunlangan).
3. **`contract_items`**: Shartnoma tovarlari. Pullik miqdor (`paid_quantity`) va bepul bonus miqdor (`bonus_quantity`) alohida saqlanadi.
4. **`delivery_batches` & `delivery_batch_items`**: Kelgan tovarlar partiyasi. Qaysi sanada qancha pullik va qancha bonus tovar kelganligi saqlanadi.
5. **`penalties`**: Shartnoma bo‘yicha hisoblangan jarima yozuvi. Hisoblash sanasi, kechikkan kunlar, hisoblangan va limit (50%) summalari, rahbar tasdig‘i (`approved_amount`) hamda kun-kunlik breakdown JSON formatida saqlanadi.
6. **`payment_milestones`**: Shartnomaning to‘lov bosqichlari (Avans 30%, Yetkazib berish 70%). Bosqich holati saqlanmaydi, balki to‘lov reestri asosida doimiy dinamik hisoblanadi.
7. **`budget_items`**: Smeta qatorlari. Yil, xarajat moddasi va oy bo‘yicha ajratilgan byudjet mablag‘lari. `(financial_year, cost_item, month)` bo‘yicha noyob cheklov o‘rnatilgan.
8. **`payments`**: To‘lovlar reestri. Yozuvlar hech qachon o‘chirilmaydi. Xato bo‘lsa `status = 'voided'` qilinadi va `void_reason` hamda `voided_by` qayd etiladi.
