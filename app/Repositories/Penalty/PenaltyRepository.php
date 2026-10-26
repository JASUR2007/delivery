<?php

namespace App\Repositories\Penalty;

use App\Models\Penalty;
use Illuminate\Database\Eloquent\Collection;

class PenaltyRepository implements PenaltyRepositoryInterface {
    public function findById(int $id): ?Penalty {
        return Penalty::find($id);
    }

    public function getByContractId(int $contractId): Collection {
        return Penalty::with('reviewer')
            ->where('contract_id', $contractId)
            ->latest('id')
            ->get();
    }

    public function findLatestForContract(int $contractId): ?Penalty {
        return Penalty::with('reviewer')
            ->where('contract_id', $contractId)
            ->latest('id')
            ->first();
    }

    public function create(array $attributes): Penalty {
        return Penalty::create($attributes);
    }

    public function update(Penalty $penalty, array $attributes): bool {
        return $penalty->update($attributes);
    }
}
