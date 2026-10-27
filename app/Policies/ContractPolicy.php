<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy {
    public function viewAny(User $user): bool {
        return true;
    }

    public function view(User $user, Contract $contract): bool {
        if ($user->isSupervisor() || $user->isAccountant()) {
            return true;
        }

        return $contract->created_by === $user->id;
    }

    public function create(User $user): bool {
        return $user->isExecutor();
    }

    public function activate(User $user, Contract $contract): bool {
        return $user->isExecutor() && $contract->created_by === $user->id;
    }

    public function recordDelivery(User $user, Contract $contract): bool {
        return $user->isExecutor() && $contract->created_by === $user->id;
    }
}
