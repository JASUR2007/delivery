<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryBatchItemResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'contract_item_id' => $this->contract_item_id,
            'item_name' => $this->contractItem?->item_name,
            'delivered_paid_quantity' => $this->delivered_paid_quantity,
            'delivered_bonus_quantity' => $this->delivered_bonus_quantity,
        ];
    }
}
