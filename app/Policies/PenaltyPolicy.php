<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class PenaltyPolicy {
    public function calculate(User $user, Contract $contract): bool {
        if ($user->isSupervisor() || $user->isAccountant()) {
            return true;
        }

        return $contract->created_by === $user->id;
    }

    public function review(User $user): bool {
        return $user->isSupervisor();
    }
}
