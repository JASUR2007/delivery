# API Hujjatlari (API Documentation)

Mazkur tizim to‘liq RESTful API arxitekturasi asosida qurilgan. API orqali autentifikatsiya, shartnomalarni boshqarish, partiyalarni qabul qilish, jarimalarni hisoblash/tasdiqlash hamda buxgalteriya to‘lovlarini yuritish mumkin.

> [!NOTE]
> Loyihada **Swagger UI** interaktiv hujjatlari o‘rnatilgan bo‘lib, brauzerda quyidagi manzilda mavjud:
> - **Swagger UI:** `http://localhost:8000/api/documentation`

---

## 1. Autentifikatsiya (Authentication)

### 1.1 Demo foydalanuvchilar ro‘yxati
* **URL:** `GET /api/auth/demo-users`
* **Ruxsat:** Ochiq
* **Javob:**
```json
{
  "users": [
    {
      "id": 1,
      "name": "Alisher Zokirov",
      "email": "ijrochi@davlat.uz",
      "role": "executor",
      "role_label": "Ijrochi"
    },
    {
      "id": 2,
      "name": "Botir Qodirov",
      "email": "boshliq@davlat.uz",
      "role": "supervisor",
      "role_label": "Boshliq"
    },
    {
      "id": 3,
      "name": "Dilshod Umarov",
      "email": "buxgalter@davlat.uz",
      "role": "accountant",
      "role_label": "Buxgalter"
    }
  ]
}
```

### 1.2 Tizimga kirish (Login)
* **URL:** `POST /api/auth/login`
* **Request Body:**
```json
{
  "email": "ijrochi@davlat.uz",
  "password": "password"
}
```
* **Javob (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "Alisher Zokirov",
    "email": "ijrochi@davlat.uz",
    "role": "executor",
    "role_label": "Ijrochi"
  },
  "token": "1|abcdef123456..."
}
```

### 1.3 Demo rolni tezkor almashtirish
* **URL:** `POST /api/auth/switch-demo-user`
* **Request Body:**
```json
{
  "user_id": 2
}
```

---

## 2. Shartnomalar (Contracts)

### 2.1 Shartnomalar ro‘yxatini olish
* **URL:** `GET /api/contracts`
* **Header:** `Authorization: Bearer <TOKEN>`
* **Ruxsat:** 
  - `Ijrochi`: faqat o‘zi yaratgan shartnomalarni ko‘radi.
  - `Boshliq` va `Buxgalter`: barcha shartnomalarni ko‘radi.

### 2.2 Yangi shartnoma yaratish
* **URL:** `POST /api/contracts`
* **Ruxsat:** Faqat `Ijrochi`
* **Request Body:**
```json
{
  "contract_number": "SH-2026-005",
  "supplier_name": "Farm Med Servis MCHJ",
  "start_date": "2026-03-02",
  "duration_days": 10,
  "items": [
    {
      "item_name": "Dori vositalari to'plami",
      "paid_quantity": 1000,
      "bonus_quantity": 50,
      "unit_price": "2.50"
    }
  ]
}
```
* **Javob (201 Created):** Yaratilgan shartnoma, avtomatik hisoblangan muddat sanasi (`deadline_date = 2026-03-12`) hamda 2 ta avtomatik to‘lov bosqichi (Avans 30% — 750.00 va Yetkazib berish 70% — 1750.00).

### 2.3 Shartnoma tafsilotlari
* **URL:** `GET /api/contracts/{id}`
* **Ruxsat:** O‘z shartnomasi (Ijrochi uchun) yoki Boshliq/Buxgalter.

### 2.4 Shartnomani faollashtirish
* **URL:** `POST /api/contracts/{id}/activate`
* **Ruxsat:** Faqat shartnoma yaratuvchisi (`Ijrochi`).
* **Qoida:** Shartnomada kamida 1 ta tovar bo‘lishi shart. Faollashgach tovar qatorlari muzlatiladi.

---

## 3. Tovarlar partiyasini qabul qilish (Deliveries)

### 3.1 Partiyalar ro‘yxati
* **URL:** `GET /api/contracts/{id}/delivery-batches`

### 3.2 Yangi partiya kiritish
* **URL:** `POST /api/contracts/{id}/delivery-batches`
* **Ruxsat:** Faqat `Ijrochi`
* **Request Body:**
```json
{
  "delivery_date": "2026-03-14",
  "notes": "Birinchi qisman partiya keldi",
  "items": [
    {
      "contract_item_id": 1,
      "delivered_paid_quantity": 600,
      "delivered_bonus_quantity": 0
    }
  ]
}
```
* **Qoidalar:**
  1. Shartnoma faol bo‘lishi kerak.
  2. Qoldiqdan ortiqcha miqdor kiritilsa, aniq xatolik qaytariladi (`422 Unprocessable Entity`).
  3. Pullik tovarlar to‘liq qabul qilinsa, shartnoma holati avtomatik `Yakunlangan` (Completed) bo‘ladi.

---

## 4. Kechikish va Jarima hisobi (Penalties)

### 4.1 Jarimani oldindan hisoblash (Preview)
* **URL:** `GET /api/contracts/{id}/penalties/preview?calculation_date=2026-03-16`
* **Javob:**
```json
{
  "preview": {
    "contract_id": 1,
    "calculation_date": "2026-03-16",
    "deadline_date": "2026-03-12",
    "delay_days": 4,
    "calculated_amount": "35.00",
    "cap_amount": "1250.00",
    "breakdown": [
      {
        "date": "2026-03-13",
        "undelivered_amount": "2500.00",
        "daily_penalty": "12.50"
      },
      {
        "date": "2026-03-14",
        "undelivered_amount": "2500.00",
        "daily_penalty": "12.50"
      },
      {
        "date": "2026-03-15",
        "undelivered_amount": "1000.00",
        "daily_penalty": "5.00"
      },
      {
        "date": "2026-03-16",
        "undelivered_amount": "1000.00",
        "daily_penalty": "5.00"
      }
    ]
  }
}
```

### 4.2 Jarimani hisoblab, boshliq tasdig‘iga yuborish
* **URL:** `POST /api/contracts/{id}/penalties`
* **Request Body:**
```json
{
  "calculation_date": "2026-03-15"
}
```

### 4.3 Jarimani tasdiqlash yoki rad etish
* **URL:** `POST /api/penalties/{id}/review`
* **Ruxsat:** Faqat `Boshliq` (Supervisor)
* **Request Body:**
```json
{
  "status": "approved",
  "approved_amount": "30.00",
  "notes": "Rahbar buyrug'i bilan tasdiqlandi"
}
```

---

## 5. Buxgalteriya va To‘lovlar (Payments)

### 5.1 Yangi to‘lov kiritish
* **URL:** `POST /api/payments`
* **Ruxsat:** Faqat `Buxgalter` (Accountant)
* **Request Body:**
```json
{
  "contract_id": 1,
  "milestone_id": 1,
  "budget_item_id": 1,
  "amount": "750.00",
  "payment_date": "2026-03-03",
  "payment_number": "TO-2026-0012",
  "comment": "Avans to'lovi 30%"
}
```
* **Tekshiruvlar (qat’iy tartibda):**
  1. Shartnoma Faol (yoki Yakunlangan) bo‘lishi kerak.
  2. Bosqichning to‘lash sharti bajarilgan bo‘lishi kerak.
  3. Oldingi bosqich to‘liq to‘langan bo‘lishi kerak.
  4. Summa noldan katta va bosqich qoldig‘idan oshmasligi kerak.
  5. Sana smeta yil/oyiga mos bo‘lishi va summa oyning erkin smeta qoldig‘idan oshmasligi kerak.

### 5.2 To‘lovni bekor qilish (Void)
* **URL:** `POST /api/payments/{id}/void`
* **Ruxsat:** Faqat `Buxgalter`
* **Request Body:**
```json
{
  "void_reason": "Buxgalter 1000 o‘rniga adashib kiritgan, aslida 900 edi"
}
```
* **Natija:** To‘lov o‘chirilmaydi, holati `voided` bo‘ladi, bosqich qoldig‘i va smeta qoldig‘i qayta hisoblanadi.

---

## 6. Smeta / Byudjet (Budget Items)

### 6.1 Smeta qatorlari ro‘yxati
* **URL:** `GET /api/budget-items`

### 6.2 Yangi smeta qatori kiritish
* **URL:** `POST /api/budget-items`
* **Ruxsat:** Faqat `Buxgalter`
* **Request Body:**
```json
{
  "financial_year": 2026,
  "cost_item": "Dori vositalari",
  "month": 5,
  "allocated_amount": "2500.00",
  "notes": "May oyi smetasi"
}
```
