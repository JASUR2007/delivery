<?php

namespace App\Policies;

use App\Models\User;

class BudgetItemPolicy {
    public function viewAny(User $user): bool {
        return true;
    }

    public function manage(User $user): bool {
        return $user->isAccountant();
    }
}
