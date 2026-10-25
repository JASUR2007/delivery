<?php

namespace App\Enums;

enum PaymentStatus: string {
    case Active = 'active';
    case Voided = 'voided';

    public function label(): string {
        return match ($this) {
            self::Active => 'Faol',
            self::Voided => 'Bekor qilingan',
        };
    }
}
