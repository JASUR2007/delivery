<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMilestoneResource extends JsonResource {
    public function toArray(Request $request): array {
        $paidAmount = $this->getPaidAmount();
        $remainingBalance = $this->getRemainingBalance();
        $computedStatus = $this->getComputedStatus();

        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'step_order' => $this->step_order,
            'name' => $this->name,
            'percentage' => $this->percentage,
            'amount' => $this->amount,
            'condition_type' => $this->condition_type->value,
            'condition_label' => $this->condition_type->label(),
            'is_condition_met' => $this->isConditionMet(),
            'due_date' => $this->due_date?->toDateString(),
            'paid_amount' => $paidAmount,
            'remaining_balance' => $remainingBalance,
            'status' => $computedStatus->value,
            'status_label' => $computedStatus->label(),
        ];
    }
}
