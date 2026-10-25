<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryBatchItem extends Model {
    use HasFactory;

    protected $fillable = [
        'delivery_batch_id',
        'contract_item_id',
        'delivered_paid_quantity',
        'delivered_bonus_quantity',
    ];

    protected function casts(): array {
        return [
            'delivered_paid_quantity' => 'integer',
            'delivered_bonus_quantity' => 'integer',
        ];
    }

    public function deliveryBatch(): BelongsTo {
        return $this->belongsTo(DeliveryBatch::class);
    }

    public function contractItem(): BelongsTo {
        return $this->belongsTo(ContractItem::class);
    }
}
