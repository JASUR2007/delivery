<?php

namespace App\Repositories\Penalty;

use App\Models\Penalty;
use Illuminate\Database\Eloquent\Collection;

interface PenaltyRepositoryInterface {
    public function findById(int $id): ?Penalty;

    public function getByContractId(int $contractId): Collection;

    public function findLatestForContract(int $contractId): ?Penalty;

    public function create(array $attributes): Penalty;

    public function update(Penalty $penalty, array $attributes): bool;
}
