<?php

namespace App\Repositories\Accounting;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface {
    public function findById(int $id): ?Payment;

    public function findByIdWithDetails(int $id): ?Payment;

    public function getByContractId(int $contractId): Collection;

    public function create(array $attributes): Payment;

    public function update(Payment $payment, array $attributes): bool;
}
