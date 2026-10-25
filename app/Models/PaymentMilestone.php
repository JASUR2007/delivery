<?php

namespace App\Models;

use App\Enums\MilestoneCondition;
use App\Enums\MilestoneStatus;
use App\Enums\PaymentStatus;
use App\Services\Money\MoneyCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMilestone extends Model {
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'step_order',
        'name',
        'percentage',
        'amount',
        'condition_type',
        'due_date',
    ];

    protected function casts(): array {
        return [
            'step_order' => 'integer',
            'percentage' => 'string',
            'amount' => 'string',
            'condition_type' => MilestoneCondition::class,
            'due_date' => 'date',
        ];
    }

    public function getAmountAttribute($value): string {
        return MoneyCalculator::format((string) $value);
    }

    public function contract(): BelongsTo {
        return $this->belongsTo(Contract::class);
    }

    public function payments(): HasMany {
        return $this->hasMany(Payment::class, 'milestone_id');
    }

    public function activePayments(): HasMany {
        return $this->hasMany(Payment::class, 'milestone_id')->where('status', PaymentStatus::Active->value);
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
        $remaining = MoneyCalculator::sub((string) $this->amount, $this->getPaidAmount());

        return MoneyCalculator::isLessThan($remaining, '0.00') ? '0.00' : MoneyCalculator::format($remaining);
    }

    public function isConditionMet(): bool {
        if ($this->condition_type === MilestoneCondition::ContractActivated) {
            return $this->contract->isActive() || $this->contract->isCompleted();
        }

        if ($this->condition_type === MilestoneCondition::GoodsFullyDelivered) {
            return $this->contract->arePaidGoodsFullyDelivered();
        }

        return false;
    }

    public function getComputedStatus(?Carbon $asOfDate = null): MilestoneStatus {
        $asOfDate = $asOfDate ?? Carbon::today();
        $paidAmount = $this->getPaidAmount();
        $isConditionMet = $this->isConditionMet();

        if (MoneyCalculator::isEqual($paidAmount, $this->amount)) {
            return MilestoneStatus::Paid;
        }

        if (! $isConditionMet) {
            return MilestoneStatus::Planned;
        }

        if ($this->due_date !== null && $asOfDate->isAfter($this->due_date)) {
            return MilestoneStatus::Overdue;
        }

        if (MoneyCalculator::isGreaterThan($paidAmount, '0.00')) {
            return MilestoneStatus::PartiallyPaid;
        }

        return MilestoneStatus::ReadyToPay;
    }
}
