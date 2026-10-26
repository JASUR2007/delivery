<?php

namespace App\DTOs\Delivery;

class DeliveryBatchItemDTO {
    public function __construct(
        public readonly int $contractItemId,
        public readonly int $deliveredPaidQuantity,
        public readonly int $deliveredBonusQuantity,
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            contractItemId: (int) $data['contract_item_id'],
            deliveredPaidQuantity: (int) ($data['delivered_paid_quantity'] ?? 0),
            deliveredBonusQuantity: (int) ($data['delivered_bonus_quantity'] ?? 0),
        );
    }
}
