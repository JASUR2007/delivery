<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'contract_number' => $this->contract_number,
            'supplier_name' => $this->supplier_name,
            'start_date' => $this->start_date->toDateString(),
            'duration_days' => $this->duration_days,
            'deadline_date' => $this->deadline_date->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'total_paid_amount' => $this->getTotalPaidAmount(),
            'total_paid_quantity' => $this->getTotalPaidQuantity(),
            'total_bonus_quantity' => $this->getTotalBonusQuantity(),
            'delivered_paid_quantity' => $this->getDeliveredPaidQuantity(),
            'delivered_bonus_quantity' => $this->getDeliveredBonusQuantity(),
            'remaining_paid_quantity' => $this->getRemainingPaidQuantity(),
            'remaining_bonus_quantity' => $this->getRemainingBonusQuantity(),
            'are_paid_goods_fully_delivered' => $this->arePaidGoodsFullyDelivered(),
            'latest_approved_penalty_amount' => $this->getLatestApprovedPenaltyAmount(),
            'items' => ContractItemResource::collection($this->whenLoaded('items')),
            'delivery_batches' => DeliveryBatchResource::collection($this->whenLoaded('deliveryBatches')),
            'payment_milestones' => PaymentMilestoneResource::collection($this->whenLoaded('paymentMilestones')),
            'latest_penalty' => new PenaltyResource($this->whenLoaded('latestPenalty')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
