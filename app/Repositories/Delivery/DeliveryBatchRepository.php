<?php

namespace App\Repositories\Delivery;

use App\Models\DeliveryBatch;
use Illuminate\Database\Eloquent\Collection;

class DeliveryBatchRepository implements DeliveryBatchRepositoryInterface {
    public function findById(int $id): ?DeliveryBatch {
        return DeliveryBatch::find($id);
    }

    public function getByContractId(int $contractId): Collection {
        return DeliveryBatch::with(['creator', 'items.contractItem'])
            ->where('contract_id', $contractId)
            ->orderBy('delivery_date')
            ->get();
    }

    public function create(array $attributes): DeliveryBatch {
        return DeliveryBatch::create($attributes);
    }
}
