# Tizim Arxitekturasi (Architecture Documentation)

Mazkur ilova **Senior Architecture** tamoyillari asosida, Clean Architecture va Domain-Driven Design (DDD) yondashuvlaridan foydalangan holda loyihalashtirilgan. Kodda yashirin qisqartmalar yo‘q, barcha klasslar va metodlar aniq nomlangan, biznes logika kontrollerlardan ajratilgan.

---

## 1. Arxitektura qatlamlari (Layered Architecture)

```
[ Brauzer / Mijoz (React + Tailwind / Swagger / REST API) ]
                          │
                          ▼
            [ Http Layer: Routing & Middleware ]
        (Sanctum, HandleInertiaRequests, Role Authorization)
                          │
                          ▼
            [ Presentation: Controllers ]
        (Web: DashboardController, Api: ApiControllers)
                          │
               ┌──────────┴──────────┐
               ▼                     ▼
     [ Form Requests ]       [ API Resources ]
  (Validatsiya va ruxsat)  (JSON transformatsiya)
               │                     │
               ▼                     │
         [ DTOs Layer ]              │
    (Typesafe ma'lumot uzatish)      │
               │                     │
               ▼                     │
    [ Domain / Services Layer ] <────┘
 (ContractService, DeliveryService, PenaltyService, AccountingService)
               │
               ▼
   [ Repositories Layer (Interfaces) ]
 (ContractRepository, PaymentRepository, BudgetItemRepository...)
               │
               ▼
    [ Eloquent Models & Enums ]
 (Contract, ContractItem, DeliveryBatch, Penalty, PaymentMilestone, BudgetItem, Payment)
               │
               ▼
 [ Database Layer: PostgreSQL / SQLite with DB Transactions & Pessimistic Locks ]
```

---

## 2. Asosiy modullar va mas’uliyatlar

### 2.1 Domain DTOs (`app/DTOs`)
Kontrollerlar va servislar o‘rtasida to‘g‘ridan-to‘g‘ri `$request->all()` array uzatilmaydi. Buning o‘rniga aniq tiplashtirilgan DTO klasslari ishlatiladi:
* `CreateContractDTO`, `ContractItemDTO`
* `CreateDeliveryBatchDTO`, `DeliveryBatchItemDTO`
* `PenaltyCalculationDTO`, `ReviewPenaltyDTO`
* `CreatePaymentDTO`, `VoidPaymentDTO`
* `CreateBudgetItemDTO`

### 2.2 Servislar qatlami (`app/Services`)
Barcha hisob-kitoblar va biznes qoidalar servislar ichida mujassamlashgan:
* **`MoneyCalculator`:** `bcmath` kutubxonasi yordamida har qanday float xatoliklaridan holi bo‘lgan yuqori aniqlikdagi hisoblash xizmati. Bankcha yaxlitlash (Half-up: `>= 0.005` yuqoriga) qoidasiga amal qiladi.
* **`ContractService`:** Shartnoma yaratish, avans (30%) va yetkazib berish (70%) bosqichlarini hisoblash, faollashtirish va pullik tovarlar to‘liq kelganda shartnomani avtomatik `Completed` qilish.
* **`DeliveryService`:** Tovarlar partiyasini qabul qilish, pullik va bonus qoldiqlarni qat’iy alohida tekshirish va ortiqcha kiritishni bloklash.
* **`PenaltyCalculatorService`:** Kechikish kunlari, 0.5% kunlik stavka, muddatdagi kelmagan summaning 50% lik chegara limiti (Cap) hamda partiya yetkazilgan sanadan keyingi kundan boshlab hisobga olinish qoidalarini yuritadi.
* **`PenaltyService`:** Jarimani hisoblash, saqlash, rahbar (Boshliq) tomonidan tasdiqlash (shu jumladan summani o‘zgartirib tasdiqlash) yoki rad etish amallari.
* **`AccountingService`:** Buxgalteriya to‘lovlarini yozish va bekor qilish (voiding). 5 bosqichli qat’iy tekshiruv va poyga holatlariga (race condition) qarshi pesimistik tranzaksiya blokirovkasi (`lockForUpdate()`).
* **`BudgetService`:** Yillik va oylik smeta qatorlarini yuritish va qoldiq monitoringi.

### 2.3 Repozitoriylar qatlami (`app/Repositories`)
Ma’lumotlar bazasi so‘rovlari interfeyslar orqali ajratilgan (`ContractRepositoryInterface`, `PaymentRepositoryInterface`, `BudgetItemRepositoryInterface` va h.k.). Bu kodni testlashni yengillashtiradi va kelajakda boshqa saqlash mexanizmlariga o‘tishni osonlashtiradi.

### 2.4 Xavfsizlik va Ruxsatlar (`app/Policies`)
Foydalanuvchi huquqlari faqat frontend tugmalarini yashirish bilan cheklanmay, server qatlamida Laravel Policy va Gate orqali tekshiriladi:
* `ContractPolicy`: Ijrochi faqat o‘z shartnomalarini ko‘radi va tahrirlaydi. Boshliq va Buxgalter barcha shartnomalarni ko‘radi.
* `PenaltyPolicy`: Faqat Boshliq jarimani tasdiqlash/rad etish huquqiga ega.
* `PaymentPolicy`: Faqat Buxgalter to‘lov kiritishi va uni bekor qilishi mumkin.
* `BudgetItemPolicy`: Faqat Buxgalter yangi smeta ochishi mumkin.

### 2.5 Tranzaksiya va Bir vaqtda ishlash (Concurrency Control)
Ikki buxgalter bir vaqtning o‘zida bitta smeta oyining oxirgi mablag‘ini ishlatishga yoki bir bosqichni to‘lashga uringan holatda `lockForUpdate()` orqali tegishli yozuvlar qulflanadi. Biri muvaffaqiyatli o‘tib qoldiqni kamaytiradi, ikkinchisi qulf yechilgach yangilangan qoldiqni ko‘rib xatolik oladi. Tizimda balans hech qachon manfiy bo‘lmaydi.
