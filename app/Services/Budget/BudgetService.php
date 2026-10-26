<?php

namespace App\Services\Budget;

use App\DTOs\Budget\CreateBudgetItemDTO;
use App\Models\BudgetItem;
use App\Repositories\Budget\BudgetItemRepositoryInterface;
use App\Services\Money\MoneyCalculator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class BudgetService {
    public function __construct(
        protected BudgetItemRepositoryInterface $budgetItemRepository,
    ) {}

    public function getAll(): Collection {
        return $this->budgetItemRepository->getAll();
    }

    public function getByYear(int $year): Collection {
        return $this->budgetItemRepository->getByYear($year);
    }

    public function createBudgetItem(CreateBudgetItemDTO $dto): BudgetItem {
        if ($dto->month < 1 || $dto->month > 12) {
            throw new InvalidArgumentException('Oy 1 dan 12 gacha bo‘lishi kerak.');
        }

        if (! MoneyCalculator::isPositive($dto->allocatedAmount)) {
            throw new InvalidArgumentException('Ajratilgan summa noldan katta bo‘lishi shart.');
        }

        $existing = $this->budgetItemRepository->findByYearCostMonth($dto->financialYear, $dto->costItem, $dto->month);
        if ($existing) {
            throw new InvalidArgumentException('Bu yil, modda va oy bo‘yicha smeta allaqachon mavjud.');
        }

        return $this->budgetItemRepository->create([
            'financial_year' => $dto->financialYear,
            'cost_item' => $dto->costItem,
            'month' => $dto->month,
            'allocated_amount' => MoneyCalculator::format($dto->allocatedAmount),
            'notes' => $dto->notes,
        ]);
    }
}
