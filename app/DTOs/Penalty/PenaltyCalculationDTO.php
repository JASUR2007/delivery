<?php

namespace App\DTOs\Penalty;

class PenaltyCalculationDTO {
    /**
     * @param  array<int, array{date: string, undelivered_amount: string, daily_penalty: string}>  $breakdown
     */
    public function __construct(
        public readonly int $contractId,
        public readonly string $calculationDate,
        public readonly string $deadlineDate,
        public readonly int $delayDays,
        public readonly string $calculatedAmount,
        public readonly string $capAmount,
        public readonly array $breakdown,
    ) {}
}
