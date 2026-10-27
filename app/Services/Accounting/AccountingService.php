<?php

namespace App\Services\Accounting;

use App\DTOs\Payment\CreatePaymentDTO;
use App\DTOs\Payment\VoidPaymentDTO;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentValidationException;
use App\Models\BudgetItem;
use App\Models\Payment;
use App\Models\PaymentMilestone;
use App\Repositories\Accounting\PaymentMilestoneRepositoryInterface;
use App\Repositories\Accounting\PaymentRepositoryInterface;
use App\Repositories\Budget\BudgetItemRepositoryInterface;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Services\Money\MoneyCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingService {
    public function __construct(
        protected ContractRepositoryInterface $contractRepository,
        protected PaymentMilestoneRepositoryInterface $milestoneRepository,
        protected BudgetItemRepositoryInterface $budgetItemRepository,
        protected PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function recordPayment(CreatePaymentDTO $dto): Payment {
        return DB::transaction(function () use ($dto): Payment {
            $budgetItem = BudgetItem::where('id', $dto->budgetItemId)->lockForUpdate()->first();
            if (! $budgetItem) {
                throw new PaymentValidationException('Smeta qatori topilmadi.');
            }

            $milestone = PaymentMilestone::with(['contract.items', 'payments'])
                ->where('id', $dto->milestoneId)
                ->lockForUpdate()
                ->first();

            if (! $milestone) {
                throw new PaymentValidationException('To‘lov bosqichi topilmadi.');
            }

            $contract = $milestone->contract;

            // 1. Shartnoma Faol (yoki Yakunlangan) bo‘lishi kerak.
            if (! $contract->isActive() && ! $contract->isCompleted()) {
                throw new PaymentValidationException('Shartnoma faol yoki yakunlangan holatda bo‘lishi kerak.');
            }

            // 2. Bosqichning to‘lash sharti bajarilgan bo‘lishi kerak.
            if (! $milestone->isConditionMet()) {
                throw new PaymentValidationException('Bosqichning to‘lash sharti hali bajarilmagan.');
            }

            // 3. Oldingi bosqich to‘liq to‘langan bo‘lishi kerak.
            $previousMilestones = PaymentMilestone::with('payments')
                ->where('contract_id', $contract->id)
                ->where('step_order', '<', $milestone->step_order)
                ->orderBy('step_order')
                ->lockForUpdate()
                ->get();

            foreach ($previousMilestones as $prevMilestone) {
                if (MoneyCalculator::isPositive($prevMilestone->getRemainingBalance())) {
                    throw new PaymentValidationException('Oldingi bosqich to‘liq to‘langan bo‘lishi kerak.');
                }
            }

            // 4. Summa noldan katta va bosqichning qolgan summasidan ko‘p emas.
            $formattedAmount = MoneyCalculator::format($dto->amount);

            if (! MoneyCalculator::isPositive($formattedAmount)) {
                throw new PaymentValidationException('To‘lov summasi noldan katta bo‘lishi kerak.');
            }

            $milestoneRemaining = $milestone->getRemainingBalance();
            if (MoneyCalculator::isGreaterThan($formattedAmount, $milestoneRemaining)) {
                throw new PaymentValidationException('To‘lov summasi bosqichning qolgan summasidan ko‘p bo‘lishi mumkin emas.');
            }

            // 5. To‘lov sanasi smeta moliyaviy yiliga mos va summa shu oyning smeta qoldig‘idan ko‘p emas.
            $paymentDate = Carbon::parse($dto->paymentDate);

            if ($paymentDate->year !== $budgetItem->financial_year || $paymentDate->month !== $budgetItem->month) {
                throw new PaymentValidationException('To‘lov sanasi tanlangan smeta qatori (yil va oy)ga mos emas.');
            }

            $budgetRemaining = $budgetItem->getRemainingBalance();
            if (MoneyCalculator::isGreaterThan($formattedAmount, $budgetRemaining)) {
                throw new PaymentValidationException('To‘lov summasi ushbu oyning smeta qoldig‘idan ko‘p.');
            }

            return $this->paymentRepository->create([
                'payment_number' => $dto->paymentNumber,
                'contract_id' => $contract->id,
                'milestone_id' => $milestone->id,
                'budget_item_id' => $budgetItem->id,
                'amount' => $formattedAmount,
                'payment_date' => $dto->paymentDate,
                'status' => PaymentStatus::Active->value,
                'created_by' => $dto->createdBy,
                'voided_by' => null,
                'voided_at' => null,
                'void_reason' => null,
            ]);
        });
    }

    public function voidPayment(VoidPaymentDTO $dto): Payment {
        return DB::transaction(function () use ($dto): Payment {
            $payment = Payment::where('id', $dto->paymentId)->lockForUpdate()->first();

            if (! $payment) {
                throw new PaymentValidationException('To‘lov yozuvi topilmadi.');
            }

            if ($payment->isVoided()) {
                throw new PaymentValidationException('To‘lov allaqachon bekor qilingan.');
            }

            if (trim($dto->voidReason) === '') {
                throw new PaymentValidationException('To‘lovni bekor qilish sababi ko‘rsatilishi shart.');
            }

            $this->paymentRepository->update($payment, [
                'status' => PaymentStatus::Voided->value,
                'void_reason' => trim($dto->voidReason),
                'voided_by' => $dto->voidedBy,
                'voided_at' => Carbon::now(),
            ]);

            return $payment->fresh(['contract', 'milestone', 'budgetItem', 'creator', 'voider']);
        });
    }
}
