<?php

namespace App\DTOs\Payment;

class CreatePaymentDTO {
    public function __construct(
        public readonly int $contractId,
        public readonly int $milestoneId,
        public readonly int $budgetItemId,
        public readonly string $amount,
        public readonly string $paymentDate,
        public readonly string $paymentNumber,
        public readonly ?string $comment,
        public readonly int $createdBy,
    ) {}

    public static function fromArray(array $data, int $userId): self {
        return new self(
            contractId: (int) $data['contract_id'],
            milestoneId: (int) $data['milestone_id'],
            budgetItemId: (int) $data['budget_item_id'],
            amount: (string) $data['amount'],
            paymentDate: (string) $data['payment_date'],
            paymentNumber: (string) $data['payment_number'],
            comment: isset($data['comment']) ? (string) $data['comment'] : null,
            createdBy: $userId,
        );
    }
}
