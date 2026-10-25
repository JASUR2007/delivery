<?php

namespace App\Models;

use App\Services\Money\MoneyCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractItem extends Model {
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'item_name',
        'paid_quantity',
        'bonus_quantity',
        'unit_price',
    ];

    protected function casts(): array {
        return [
            'paid_quantity' => 'integer',
            'bonus_quantity' => 'integer',
            'unit_price' => 'string',
        ];
    }

    public function contract(): BelongsTo {
        return $this->belongsTo(Contract::class);
    }

    public function deliveryBatchItems(): HasMany {
        return $this->hasMany(DeliveryBatchItem::class);
    }

    public function getTotalPaidAmountAttribute(): string {
        return MoneyCalculator::mul((string) $this->paid_quantity, (string) $this->unit_price, 2);
    }

    public function getDeliveredPaidQuantityAttribute(): int {
        return (int) $this->deliveryBatchItems()->sum('delivered_paid_quantity');
    }

    public function getDeliveredBonusQuantityAttribute(): int {
        return (int) $this->deliveryBatchItems()->sum('delivered_bonus_quantity');
    }

    public function getRemainingPaidQuantityAttribute(): int {
        return max(0, $this->paid_quantity - $this->delivered_paid_quantity);
    }

    public function getRemainingBonusQuantityAttribute(): int {
        return max(0, $this->bonus_quantity - $this->delivered_bonus_quantity);
    }
}
