<?php

namespace App\Repositories\Contracts;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Collection;

interface ContractRepositoryInterface {
    public function findById(int $id): ?Contract;

    public function findWithDetails(int $id): ?Contract;

    public function getAllForUser(int $userId, bool $isSupervisorOrAccountant): Collection;

    public function create(array $attributes): Contract;

    public function update(Contract $contract, array $attributes): bool;

    public function existsByNumber(string $contractNumber): bool;
}
