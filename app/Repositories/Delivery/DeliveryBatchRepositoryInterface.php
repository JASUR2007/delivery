<?php

namespace App\Repositories\Delivery;

use App\Models\DeliveryBatch;
use Illuminate\Database\Eloquent\Collection;

interface DeliveryBatchRepositoryInterface {
    public function findById(int $id): ?DeliveryBatch;

    public function getByContractId(int $contractId): Collection;

    public function create(array $attributes): DeliveryBatch;
}
