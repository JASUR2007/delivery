<?php

namespace App\Http\Requests\Penalty;

use App\Enums\PenaltyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewPenaltyRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->isSupervisor() ?? false;
    }

    public function rules(): array {
        return [
            'status' => ['required', Rule::in([PenaltyStatus::Approved->value, PenaltyStatus::Rejected->value])],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
