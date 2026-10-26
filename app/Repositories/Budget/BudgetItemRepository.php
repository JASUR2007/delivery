<?php

namespace App\Repositories\Budget;

use App\Models\BudgetItem;
use Illuminate\Database\Eloquent\Collection;

class BudgetItemRepository implements BudgetItemRepositoryInterface {
    public function findById(int $id): ?BudgetItem {
        return BudgetItem::with('payments')->find($id);
    }

    public function findByIdWithLock(int $id): ?BudgetItem {
        return BudgetItem::with('payments')
            ->lockForUpdate()
            ->find($id);
    }

    public function getAll(): Collection {
        return BudgetItem::with('payments')
            ->orderBy('financial_year', 'desc')
            ->orderBy('cost_item')
            ->orderBy('month')
            ->get();
    }

    public function getByYear(int $year): Collection {
        return BudgetItem::with('payments')
            ->where('financial_year', $year)
            ->orderBy('month')
            ->get();
    }

    public function findByYearCostMonth(int $year, string $costItem, int $month): ?BudgetItem {
        return BudgetItem::where('financial_year', $year)
            ->where('cost_item', $costItem)
            ->where('month', $month)
            ->first();
    }

    public function create(array $attributes): BudgetItem {
        return BudgetItem::create($attributes);
    }

    public function update(BudgetItem $budgetItem, array $attributes): bool {
        return $budgetItem->update($attributes);
    }
}
