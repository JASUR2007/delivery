<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Services\Money\MoneyCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetItem extends Model {
    use HasFactory;

    protected $fillable = [
        'financial_year',
        'cost_item',
        'month',
        'allocated_amount',
        'notes',
    ];

    protected function casts(): array {
        return [
            'financial_year' => 'integer',
            'month' => 'integer',
            'allocated_amount' => 'string',
        ];
    }

    public function getAllocatedAmountAttribute($value): string {
        return MoneyCalculator::format((string) $value);
    }

    public function payments(): HasMany {
        return $this->hasMany(Payment::class, 'budget_item_id');
    }

    public function activePayments(): HasMany {
        return $this->hasMany(Payment::class, 'budget_item_id')->where('status', PaymentStatus::Active->value);
    }

    public function getPaidAmount(): string {
        $sum = '0.00';
        $payments = $this->relationLoaded('payments')
            ? $this->payments->where('status', PaymentStatus::Active)
            : $this->activePayments()->get();

        foreach ($payments as $payment) {
            $sum = MoneyCalculator::add($sum, (string) $payment->amount);
        }

        return $sum;
    }

    public function getRemainingBalance(): string {
        $remaining = MoneyCalculator::sub((string) $this->allocated_amount, $this->getPaidAmount());

        return MoneyCalculator::isLessThan($remaining, '0.00') ? '0.00' : MoneyCalculator::format($remaining);
    }

    public function getMonthNameUzbek(): string {
        $months = [
            1 => 'Yanvar',
            2 => 'Fevral',
            3 => 'Mart',
            4 => 'Aprel',
            5 => 'May',
            6 => 'Iyun',
            7 => 'Iyul',
            8 => 'Avgust',
            9 => 'Sentabr',
            10 => 'Oktabr',
            11 => 'Noyabr',
            12 => 'Dekabr',
        ];

        return $months[$this->month] ?? (string) $this->month;
    }
}
