<?php

namespace App\Enums;

enum PenaltyStatus: string {
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string {
        return match ($this) {
            self::Pending => 'Kutilmoqda',
            self::Approved => 'Tasdiqlangan',
            self::Rejected => 'Rad etilgan',
        };
    }
}
