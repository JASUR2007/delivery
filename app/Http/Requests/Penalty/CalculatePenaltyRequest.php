<?php

namespace App\Http\Requests\Penalty;

use Illuminate\Foundation\Http\FormRequest;

class CalculatePenaltyRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user() !== null;
    }

    public function rules(): array {
        return [
            'calculation_date' => ['nullable', 'date'],
        ];
    }
}
