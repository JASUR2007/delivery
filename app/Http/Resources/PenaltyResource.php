<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenaltyResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'calculation_date' => $this->calculation_date->toDateString(),
            'delay_days' => $this->delay_days,
            'calculated_amount' => $this->calculated_amount,
            'cap_amount' => $this->cap_amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'approved_amount' => $this->approved_amount,
            'effective_amount' => $this->getEffectiveAmount(),
            'supervisor_notes' => $this->supervisor_notes,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'breakdown' => $this->breakdown ?? [],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
