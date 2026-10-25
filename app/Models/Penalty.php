<?php

namespace App\Models;

use App\Enums\PenaltyStatus;
use App\Services\Money\MoneyCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penalty extends Model {
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'calculation_date',
        'delay_days',
        'calculated_amount',
        'cap_amount',
        'status',
        'approved_amount',
        'supervisor_notes',
        'reviewed_by',
        'reviewed_at',
        'breakdown',
    ];

    protected function casts(): array {
        return [
            'calculation_date' => 'date',
            'calculated_amount' => 'string',
            'cap_amount' => 'string',
            'approved_amount' => 'string',
            'status' => PenaltyStatus::class,
            'reviewed_at' => 'datetime',
            'breakdown' => 'array',
        ];
    }

    public function getCalculatedAmountAttribute($value): string {
        return MoneyCalculator::format((string) $value);
    }

    public function getCapAmountAttribute($value): string {
        return MoneyCalculator::format((string) $value);
    }

    public function getApprovedAmountAttribute($value): ?string {
        return $value !== null ? MoneyCalculator::format((string) $value) : null;
    }

    public function contract(): BelongsTo {
        return $this->belongsTo(Contract::class);
    }

    public function reviewer(): BelongsTo {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getEffectiveAmount(): string {
        if ($this->status !== PenaltyStatus::Approved) {
            return '0.00';
        }

        if ($this->approved_amount !== null && trim($this->approved_amount) !== '') {
            return MoneyCalculator::format($this->approved_amount);
        }

        return MoneyCalculator::format($this->calculated_amount);
    }
}
