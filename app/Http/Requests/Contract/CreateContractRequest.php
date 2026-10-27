<?php

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;

class CreateContractRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->isExecutor() ?? false;
    }

    public function rules(): array {
        return [
            'contract_number' => ['required', 'string', 'max:255', 'unique:contracts,contract_number'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.paid_quantity' => ['required', 'integer', 'min:1'],
            'items.*.bonus_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
