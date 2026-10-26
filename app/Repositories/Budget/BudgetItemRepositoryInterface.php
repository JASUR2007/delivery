<?php

namespace App\Repositories\Budget;

use App\Models\BudgetItem;
use Illuminate\Database\Eloquent\Collection;

interface BudgetItemRepositoryInterface {
    public function findById(int $id): ?BudgetItem;

    public function findByIdWithLock(int $id): ?BudgetItem;

    public function getAll(): Collection;

    public function getByYear(int $year): Collection;

    public function findByYearCostMonth(int $year, string $costItem, int $month): ?BudgetItem;

    public function create(array $attributes): BudgetItem;

    public function update(BudgetItem $budgetItem, array $attributes): bool;
}
