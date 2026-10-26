<?php

namespace App\DTOs\Contract;

class ContractItemDTO {
    public function __construct(
        public readonly string $itemName,
        public readonly int $paidQuantity,
        public readonly int $bonusQuantity,
        public readonly string $unitPrice,
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            itemName: (string) $data['item_name'],
            paidQuantity: (int) $data['paid_quantity'],
            bonusQuantity: (int) ($data['bonus_quantity'] ?? 0),
            unitPrice: (string) $data['unit_price'],
        );
    }
}
