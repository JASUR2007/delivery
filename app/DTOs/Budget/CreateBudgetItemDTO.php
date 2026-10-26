<?php

namespace App\DTOs\Budget;

class CreateBudgetItemDTO {
    public function __construct(
        public readonly int $financialYear,
        public readonly string $costItem,
        public readonly int $month,
        public readonly string $allocatedAmount,
        public readonly ?string $notes,
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            financialYear: (int) $data['financial_year'],
            costItem: (string) $data['cost_item'],
            month: (int) $data['month'],
            allocatedAmount: (string) $data['allocated_amount'],
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }
}
