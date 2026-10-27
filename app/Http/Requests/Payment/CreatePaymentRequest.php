<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->isAccountant() ?? false;
    }

    public function rules(): array {
        return [
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'milestone_id' => ['required', 'integer', 'exists:payment_milestones,id'],
            'budget_item_id' => ['required', 'integer', 'exists:budget_items,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_number' => ['required', 'string', 'max:100'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
