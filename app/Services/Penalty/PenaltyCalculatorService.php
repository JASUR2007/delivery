<?php

namespace App\Services\Penalty;

use App\DTOs\Penalty\PenaltyCalculationDTO;
use App\Models\Contract;
use App\Services\Money\MoneyCalculator;
use Carbon\Carbon;

class PenaltyCalculatorService {
    public function calculate(Contract $contract, ?string $asOfDateString = null): PenaltyCalculationDTO {
        $contract->loadMissing(['items', 'deliveryBatches.items']);

        $deadlineDate = Carbon::parse($contract->deadline_date)->startOfDay();
        $calculationDate = $asOfDateString !== null && trim($asOfDateString) !== ''
            ? Carbon::parse($asOfDateString)->startOfDay()
            : Carbon::today()->startOfDay();

        $undeliveredAtDeadline = $this->calculateUndeliveredAmountAsOf($contract, $deadlineDate);
        $capAmount = MoneyCalculator::round(MoneyCalculator::mul($undeliveredAtDeadline, '0.50', 4), 2);

        if ($calculationDate->lessThanOrEqualTo($deadlineDate)) {
            return new PenaltyCalculationDTO(
                contractId: $contract->id,
                calculationDate: $calculationDate->toDateString(),
                deadlineDate: $deadlineDate->toDateString(),
                delayDays: 0,
                calculatedAmount: '0.00',
                capAmount: $capAmount,
                breakdown: [],
            );
        }

        $currentDay = $deadlineDate->copy()->addDay();
        $delayDays = 0;
        $totalCalculatedPenalty = '0.00';
        $breakdown = [];

        while ($currentDay->lessThanOrEqualTo($calculationDate)) {
            $dayUndeliveredAmount = $this->calculateUndeliveredAmountForPenaltyDay($contract, $currentDay);

            if (MoneyCalculator::isPositive($dayUndeliveredAmount)) {
                $rawDailyPenalty = MoneyCalculator::mul($dayUndeliveredAmount, '0.005', 4);
                $dailyPenalty = MoneyCalculator::round($rawDailyPenalty, 2);

                $totalCalculatedPenalty = MoneyCalculator::add($totalCalculatedPenalty, $dailyPenalty);
                $delayDays++;

                $breakdown[] = [
                    'date' => $currentDay->toDateString(),
                    'undelivered_amount' => $dayUndeliveredAmount,
                    'daily_penalty' => $dailyPenalty,
                ];
            }

            $currentDay->addDay();
        }

        $finalAmount = MoneyCalculator::isGreaterThan($totalCalculatedPenalty, $capAmount)
            ? $capAmount
            : $totalCalculatedPenalty;

        return new PenaltyCalculationDTO(
            contractId: $contract->id,
            calculationDate: $calculationDate->toDateString(),
            deadlineDate: $deadlineDate->toDateString(),
            delayDays: $delayDays,
            calculatedAmount: $finalAmount,
            capAmount: $capAmount,
            breakdown: $breakdown,
        );
    }

    private function calculateUndeliveredAmountAsOf(Contract $contract, Carbon $date): string {
        $totalUndelivered = '0.00';

        foreach ($contract->items as $item) {
            $deliveredPaidQuantity = 0;

            foreach ($contract->deliveryBatches as $batch) {
                $batchDeliveryDate = Carbon::parse($batch->delivery_date)->startOfDay();
                if ($batchDeliveryDate->lessThanOrEqualTo($date)) {
                    foreach ($batch->items as $batchItem) {
                        if ($batchItem->contract_item_id === $item->id) {
                            $deliveredPaidQuantity += $batchItem->delivered_paid_quantity;
                        }
                    }
                }
            }

            $remainingPaidQuantity = max(0, $item->paid_quantity - $deliveredPaidQuantity);
            $itemUndeliveredAmount = MoneyCalculator::mul((string) $remainingPaidQuantity, (string) $item->unit_price, 2);
            $totalUndelivered = MoneyCalculator::add($totalUndelivered, $itemUndeliveredAmount);
        }

        return $totalUndelivered;
    }

    private function calculateUndeliveredAmountForPenaltyDay(Contract $contract, Carbon $penaltyDay): string {
        $totalUndelivered = '0.00';

        foreach ($contract->items as $item) {
            $deliveredPaidQuantity = 0;

            foreach ($contract->deliveryBatches as $batch) {
                $batchDeliveryDate = Carbon::parse($batch->delivery_date)->startOfDay();
                if ($batchDeliveryDate->lessThan($penaltyDay)) {
                    foreach ($batch->items as $batchItem) {
                        if ($batchItem->contract_item_id === $item->id) {
                            $deliveredPaidQuantity += $batchItem->delivered_paid_quantity;
                        }
                    }
                }
            }

            $remainingPaidQuantity = max(0, $item->paid_quantity - $deliveredPaidQuantity);
            $itemUndeliveredAmount = MoneyCalculator::mul((string) $remainingPaidQuantity, (string) $item->unit_price, 2);
            $totalUndelivered = MoneyCalculator::add($totalUndelivered, $itemUndeliveredAmount);
        }

        return $totalUndelivered;
    }
}
