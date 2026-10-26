<?php

namespace App\DTOs\Delivery;

class CreateDeliveryBatchDTO {
    /**
     * @param  array<int, DeliveryBatchItemDTO>  $items
     */
    public function __construct(
        public readonly int $contractId,
        public readonly string $deliveryDate,
        public readonly ?string $notes,
        public readonly array $items,
        public readonly int $createdBy,
    ) {}

    public static function fromArray(array $data, int $contractId, int $userId): self {
        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = $item instanceof DeliveryBatchItemDTO ? $item : DeliveryBatchItemDTO::fromArray($item);
        }

        return new self(
            contractId: $contractId,
            deliveryDate: (string) $data['delivery_date'],
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            items: $items,
            createdBy: $userId,
        );
    }
}
