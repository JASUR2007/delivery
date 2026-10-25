<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\PenaltyStatus;
use App\Services\Money\MoneyCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends Model {
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'supplier_name',
        'start_date',
        'duration_days',
        'deadline_date',
        'status',
        'created_by',
    ];

    protected function casts(): array {
        return [
            'start_date' => 'date',
            'duration_days' => 'integer',
            'deadline_date' => 'date',
            'status' => ContractStatus::class,
        ];
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany {
        return $this->hasMany(ContractItem::class);
    }

    public function deliveryBatches(): HasMany {
        return $this->hasMany(DeliveryBatch::class);
    }

    public function paymentMilestones(): HasMany {
        return $this->hasMany(PaymentMilestone::class)->orderBy('step_order');
    }

    public function payments(): HasMany {
        return $this->hasMany(Payment::class);
    }

    public function penalties(): HasMany {
        return $this->hasMany(Penalty::class);
    }

    public function latestPenalty(): HasOne {
        return $this->hasOne(Penalty::class)->latestOfMany();
    }

    public function isDraft(): bool {
        return $this->status === ContractStatus::Draft;
    }

    public function isActive(): bool {
        return $this->status === ContractStatus::Active;
    }

    public function isCompleted(): bool {
        return $this->status === ContractStatus::Completed;
    }

    public function getTotalPaidAmount(): string {
        $sum = '0.00';
        foreach ($this->items as $item) {
            $itemTotal = MoneyCalculator::mul((string) $item->paid_quantity, (string) $item->unit_price, 2);
            $sum = MoneyCalculator::add($sum, $itemTotal);
        }

        return $sum;
    }

    public function getTotalPaidQuantity(): int {
        return (int) $this->items->sum('paid_quantity');
    }

    public function getTotalBonusQuantity(): int {
        return (int) $this->items->sum('bonus_quantity');
    }

    public function getDeliveredPaidQuantity(): int {
        $delivered = 0;
        foreach ($this->items as $item) {
            $delivered += $item->delivered_paid_quantity;
        }

        return $delivered;
    }

    public function getDeliveredBonusQuantity(): int {
        $delivered = 0;
        foreach ($this->items as $item) {
            $delivered += $item->delivered_bonus_quantity;
        }

        return $delivered;
    }

    public function getRemainingPaidQuantity(): int {
        return max(0, $this->getTotalPaidQuantity() - $this->getDeliveredPaidQuantity());
    }

    public function getRemainingBonusQuantity(): int {
        return max(0, $this->getTotalBonusQuantity() - $this->getDeliveredBonusQuantity());
    }

    public function arePaidGoodsFullyDelivered(): bool {
        $totalPaid = $this->getTotalPaidQuantity();
        if ($totalPaid === 0) {
            return false;
        }

        return $this->getDeliveredPaidQuantity() >= $totalPaid;
    }

    public function getLatestApprovedPenaltyAmount(): string {
        $approvedPenalty = $this->penalties()
            ->where('status', PenaltyStatus::Approved->value)
            ->latest('id')
            ->first();

        if (! $approvedPenalty) {
            return '0.00';
        }

        return $approvedPenalty->getEffectiveAmount();
    }
}
