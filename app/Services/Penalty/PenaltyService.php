<?php

namespace App\Services\Penalty;

use App\DTOs\Penalty\PenaltyCalculationDTO;
use App\DTOs\Penalty\ReviewPenaltyDTO;
use App\Enums\PenaltyStatus;
use App\Exceptions\PenaltyValidationException;
use App\Models\Contract;
use App\Models\Penalty;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Penalty\PenaltyRepositoryInterface;
use App\Services\Money\MoneyCalculator;
use Carbon\Carbon;

class PenaltyService {
    public function __construct(
        protected ContractRepositoryInterface $contractRepository,
        protected PenaltyRepositoryInterface $penaltyRepository,
        protected PenaltyCalculatorService $calculatorService,
    ) {}

    public function calculateAndSave(Contract $contract, ?string $asOfDate = null): Penalty {
        $calculationDto = $this->calculatorService->calculate($contract, $asOfDate);

        return $this->penaltyRepository->create([
            'contract_id' => $contract->id,
            'calculation_date' => $calculationDto->calculationDate,
            'delay_days' => $calculationDto->delayDays,
            'calculated_amount' => $calculationDto->calculatedAmount,
            'cap_amount' => $calculationDto->capAmount,
            'status' => PenaltyStatus::Pending->value,
            'approved_amount' => null,
            'supervisor_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'breakdown' => $calculationDto->breakdown,
        ]);
    }

    public function calculatePreview(Contract $contract, ?string $asOfDate = null): PenaltyCalculationDTO {
        return $this->calculatorService->calculate($contract, $asOfDate);
    }

    public function reviewPenalty(ReviewPenaltyDTO $dto): Penalty {
        $penalty = $this->penaltyRepository->findById($dto->penaltyId);

        if (! $penalty) {
            throw new PenaltyValidationException('Jarima yozuvi topilmadi.');
        }

        if ($penalty->status !== PenaltyStatus::Pending) {
            throw new PenaltyValidationException('Faqat kutilayotgan jarimani ko‘rib chiqish mumkin.');
        }

        $approvedAmount = null;

        if ($dto->status === PenaltyStatus::Approved) {
            if ($dto->approvedAmount !== null && trim($dto->approvedAmount) !== '') {
                if (MoneyCalculator::isLessThan($dto->approvedAmount, '0.00')) {
                    throw new PenaltyValidationException('Tasdiqlangan summa manfiy bo‘lishi mumkin emas.');
                }
                $approvedAmount = MoneyCalculator::format($dto->approvedAmount);
            } else {
                $approvedAmount = $penalty->calculated_amount;
            }
        }

        $this->penaltyRepository->update($penalty, [
            'status' => $dto->status->value,
            'approved_amount' => $approvedAmount,
            'supervisor_notes' => $dto->notes,
            'reviewed_by' => $dto->reviewedBy,
            'reviewed_at' => Carbon::now(),
        ]);

        return $penalty->fresh(['reviewer', 'contract']);
    }
}
