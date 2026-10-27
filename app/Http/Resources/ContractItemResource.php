<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractItemResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'item_name' => $this->item_name,
            'paid_quantity' => $this->paid_quantity,
            'bonus_quantity' => $this->bonus_quantity,
            'unit_price' => $this->unit_price,
            'total_paid_amount' => $this->total_paid_amount,
            'delivered_paid_quantity' => $this->delivered_paid_quantity,
            'delivered_bonus_quantity' => $this->delivered_bonus_quantity,
            'remaining_paid_quantity' => $this->remaining_paid_quantity,
            'remaining_bonus_quantity' => $this->remaining_bonus_quantity,
        ];
    }
}
