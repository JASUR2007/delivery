<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryBatch extends Model {
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'delivery_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array {
        return [
            'delivery_date' => 'date',
        ];
    }

    public function contract(): BelongsTo {
        return $this->belongsTo(Contract::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany {
        return $this->hasMany(DeliveryBatchItem::class);
    }
}
