<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetItemResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'financial_year' => $this->financial_year,
            'cost_item' => $this->cost_item,
            'month' => $this->month,
            'month_name' => $this->getMonthNameUzbek(),
            'allocated_amount' => $this->allocated_amount,
            'paid_amount' => $this->getPaidAmount(),
            'remaining_balance' => $this->getRemainingBalance(),
            'notes' => $this->notes,
        ];
    }
}
