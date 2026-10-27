<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class CreateBudgetItemRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->isAccountant() ?? false;
    }

    public function rules(): array {
        return [
            'financial_year' => ['required', 'integer', 'min:2020', 'max:2050'],
            'cost_item' => ['required', 'string', 'max:255'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'allocated_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
