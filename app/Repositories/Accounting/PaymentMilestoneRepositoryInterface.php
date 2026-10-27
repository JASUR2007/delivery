<?php

namespace App\Repositories\Accounting;

use App\Models\PaymentMilestone;
use Illuminate\Database\Eloquent\Collection;

interface PaymentMilestoneRepositoryInterface {
    public function findById(int $id): ?PaymentMilestone;

    public function findByIdWithLock(int $id): ?PaymentMilestone;

    public function getByContractId(int $contractId): Collection;

    public function create(array $attributes): PaymentMilestone;
}
