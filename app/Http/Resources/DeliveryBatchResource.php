<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryBatchResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'delivery_date' => $this->delivery_date->toDateString(),
            'notes' => $this->notes,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'items' => DeliveryBatchItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
