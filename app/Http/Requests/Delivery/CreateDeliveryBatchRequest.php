<?php

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class CreateDeliveryBatchRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->isExecutor() ?? false;
    }

    public function rules(): array {
        return [
            'delivery_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.contract_item_id' => ['required', 'integer', 'exists:contract_items,id'],
            'items.*.delivered_paid_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.delivered_bonus_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
