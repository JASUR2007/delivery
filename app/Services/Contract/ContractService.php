<?php

namespace App\Services\Contract;

use App\DTOs\Contract\CreateContractDTO;
use App\Enums\ContractStatus;
use App\Enums\MilestoneCondition;
use App\Exceptions\ContractValidationException;
use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Services\Money\MoneyCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContractService {
    public function __construct(
        protected ContractRepositoryInterface $contractRepository,
    ) {}

    public function createContract(CreateContractDTO $dto): Contract {
        if ($this->contractRepository->existsByNumber($dto->contractNumber)) {
            throw new ContractValidationException('Bu raqamli shartnoma allaqachon mavjud.');
        }

        if (count($dto->items) === 0) {
            throw new ContractValidationException('Shartnomada kamida bitta tovar qatori bo‘lishi kerak.');
        }

        $startDate = Carbon::parse($dto->startDate);
        $deadlineDate = $startDate->copy()->addDays($dto->durationDays);

        return DB::transaction(function () use ($dto, $deadlineDate): Contract {
            $contract = $this->contractRepository->create([
                'contract_number' => $dto->contractNumber,
                'supplier_name' => $dto->supplierName,
                'start_date' => $dto->startDate,
                'duration_days' => $dto->durationDays,
                'deadline_date' => $deadlineDate->toDateString(),
                'status' => ContractStatus::Draft->value,
                'created_by' => $dto->createdBy,
            ]);

            $totalPaidSum = '0.00';

            foreach ($dto->items as $itemDto) {
                if ($itemDto->paidQuantity <= 0) {
                    throw new ContractValidationException('Pullik tovar soni noldan katta bo‘lishi shart.');
                }

                if ($itemDto->bonusQuantity < 0) {
                    throw new ContractValidationException('Bonus tovar soni manfiy bo‘lishi mumkin emas.');
                }

                if (! MoneyCalculator::isPositive($itemDto->unitPrice)) {
                    throw new ContractValidationException('Tovar narxi noldan katta bo‘lishi shart.');
                }

                $contract->items()->create([
                    'item_name' => $itemDto->itemName,
                    'paid_quantity' => $itemDto->paidQuantity,
                    'bonus_quantity' => $itemDto->bonusQuantity,
                    'unit_price' => MoneyCalculator::format($itemDto->unitPrice),
                ]);

                $lineTotal = MoneyCalculator::mul((string) $itemDto->paidQuantity, (string) $itemDto->unitPrice, 2);
                $totalPaidSum = MoneyCalculator::add($totalPaidSum, $lineTotal);
            }

            $advanceAmount = MoneyCalculator::round(MoneyCalculator::mul($totalPaidSum, '0.30', 4), 2);
            $deliveryAmount = MoneyCalculator::sub($totalPaidSum, $advanceAmount);

            $contract->paymentMilestones()->create([
                'step_order' => 1,
                'name' => 'Avans',
                'percentage' => '30.00',
                'amount' => $advanceAmount,
                'condition_type' => MilestoneCondition::ContractActivated->value,
                'due_date' => null,
            ]);

            $contract->paymentMilestones()->create([
                'step_order' => 2,
                'name' => 'Yetkazib berish',
                'percentage' => '70.00',
                'amount' => $deliveryAmount,
                'condition_type' => MilestoneCondition::GoodsFullyDelivered->value,
                'due_date' => $deadlineDate->toDateString(),
            ]);

            return $contract;
        });
    }

    public function activateContract(Contract $contract): Contract {
        if (! $contract->isDraft()) {
            throw new ContractValidationException('Faqat qoralama holatidagi shartnomani faollashtirish mumkin.');
        }

        if ($contract->items()->count() === 0) {
            throw new ContractValidationException('Faollashtirish uchun kamida bitta tovar qatori kerak.');
        }

        $contract->update([
            'status' => ContractStatus::Active->value,
        ]);

        return $contract->fresh();
    }

    public function checkAndCompleteContract(Contract $contract): Contract {
        if ($contract->isActive() && $contract->arePaidGoodsFullyDelivered()) {
            $contract->update([
                'status' => ContractStatus::Completed->value,
            ]);
        }

        return $contract->fresh();
    }
}
