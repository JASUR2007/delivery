<?php

namespace App\DTOs\Contract;

class CreateContractDTO {
    /**
     * @param  array<int, ContractItemDTO>  $items
     */
    public function __construct(
        public readonly string $contractNumber,
        public readonly string $supplierName,
        public readonly string $startDate,
        public readonly int $durationDays,
        public readonly array $items,
        public readonly int $createdBy,
    ) {}

    public static function fromArray(array $data, int $userId): self {
        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = $item instanceof ContractItemDTO ? $item : ContractItemDTO::fromArray($item);
        }

        return new self(
            contractNumber: (string) $data['contract_number'],
            supplierName: (string) $data['supplier_name'],
            startDate: (string) $data['start_date'],
            durationDays: (int) $data['duration_days'],
            items: $items,
            createdBy: $userId,
        );
    }
}
