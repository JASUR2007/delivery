<?php

namespace App\Repositories\Accounting;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository implements PaymentRepositoryInterface {
    public function findById(int $id): ?Payment {
        return Payment::find($id);
    }

    public function findByIdWithDetails(int $id): ?Payment {
        return Payment::with(['contract', 'milestone', 'budgetItem', 'creator', 'voider'])->find($id);
    }

    public function getByContractId(int $contractId): Collection {
        return Payment::with(['milestone', 'budgetItem', 'creator', 'voider'])
            ->where('contract_id', $contractId)
            ->latest('id')
            ->get();
    }

    public function create(array $attributes): Payment {
        return Payment::create($attributes);
    }

    public function update(Payment $payment, array $attributes): bool {
        return $payment->update($attributes);
    }
}
