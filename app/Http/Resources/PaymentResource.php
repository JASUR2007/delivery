<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'contract_id' => $this->contract_id,
            'milestone_id' => $this->milestone_id,
            'milestone_name' => $this->milestone?->name,
            'budget_item_id' => $this->budget_item_id,
            'budget_month_name' => $this->budgetItem?->getMonthNameUzbek(),
            'amount' => $this->amount,
            'payment_date' => $this->payment_date->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'voider' => new UserResource($this->whenLoaded('voider')),
            'voided_at' => $this->voided_at?->toIso8601String(),
            'void_reason' => $this->void_reason,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
