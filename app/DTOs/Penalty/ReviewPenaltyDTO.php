<?php

namespace App\DTOs\Penalty;

use App\Enums\PenaltyStatus;

class ReviewPenaltyDTO {
    public function __construct(
        public readonly int $penaltyId,
        public readonly PenaltyStatus $status,
        public readonly ?string $approvedAmount,
        public readonly ?string $notes,
        public readonly int $reviewedBy,
    ) {}

    public static function fromArray(array $data, int $penaltyId, int $userId): self {
        return new self(
            penaltyId: $penaltyId,
            status: PenaltyStatus::from($data['status']),
            approvedAmount: isset($data['approved_amount']) && $data['approved_amount'] !== null && $data['approved_amount'] !== ''
                ? (string) $data['approved_amount']
                : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            reviewedBy: $userId,
        );
    }
}
