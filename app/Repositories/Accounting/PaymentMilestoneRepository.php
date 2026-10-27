<?php

namespace App\Repositories\Accounting;

use App\Models\PaymentMilestone;
use Illuminate\Database\Eloquent\Collection;

class PaymentMilestoneRepository implements PaymentMilestoneRepositoryInterface {
    public function findById(int $id): ?PaymentMilestone {
        return PaymentMilestone::with(['contract.items', 'payments'])->find($id);
    }

    public function findByIdWithLock(int $id): ?PaymentMilestone {
        return PaymentMilestone::with(['contract.items', 'payments'])
            ->lockForUpdate()
            ->find($id);
    }

    public function getByContractId(int $contractId): Collection {
        return PaymentMilestone::with(['payments.creator', 'payments.voider'])
            ->where('contract_id', $contractId)
            ->orderBy('step_order')
            ->get();
    }

    public function create(array $attributes): PaymentMilestone {
        return PaymentMilestone::create($attributes);
    }
}
