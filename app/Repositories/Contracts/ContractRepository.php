<?php

namespace App\Repositories\Contracts;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Collection;

class ContractRepository implements ContractRepositoryInterface {
    public function findById(int $id): ?Contract {
        return Contract::find($id);
    }

    public function findWithDetails(int $id): ?Contract {
        return Contract::with([
            'creator',
            'items.deliveryBatchItems',
            'deliveryBatches.items.contractItem',
            'deliveryBatches.creator',
            'paymentMilestones.payments.creator',
            'paymentMilestones.payments.voider',
            'penalties.reviewer',
            'latestPenalty.reviewer',
        ])->find($id);
    }

    public function getAllForUser(int $userId, bool $isSupervisorOrAccountant): Collection {
        $query = Contract::with([
            'creator',
            'items',
            'latestPenalty',
            'paymentMilestones.payments',
        ])->latest('id');

        if (! $isSupervisorOrAccountant) {
            $query->where('created_by', $userId);
        }

        return $query->get();
    }

    public function create(array $attributes): Contract {
        return Contract::create($attributes);
    }

    public function update(Contract $contract, array $attributes): bool {
        return $contract->update($attributes);
    }

    public function existsByNumber(string $contractNumber): bool {
        return Contract::where('contract_number', $contractNumber)->exists();
    }
}
