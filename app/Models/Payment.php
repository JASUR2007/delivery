<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Services\Money\MoneyCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model {
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'contract_id',
        'milestone_id',
        'budget_item_id',
        'amount',
        'payment_date',
        'status',
        'created_by',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array {
        return [
            'amount' => 'string',
            'payment_date' => 'date',
            'status' => PaymentStatus::class,
            'voided_at' => 'datetime',
        ];
    }

    public function getAmountAttribute($value): string {
        return MoneyCalculator::format((string) $value);
    }

    public function contract(): BelongsTo {
        return $this->belongsTo(Contract::class);
    }

    public function milestone(): BelongsTo {
        return $this->belongsTo(PaymentMilestone::class, 'milestone_id');
    }

    public function budgetItem(): BelongsTo {
        return $this->belongsTo(BudgetItem::class, 'budget_item_id');
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider(): BelongsTo {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isActive(): bool {
        return $this->status === PaymentStatus::Active;
    }

    public function isVoided(): bool {
        return $this->status === PaymentStatus::Voided;
    }
}
