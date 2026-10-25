<?php

namespace App\Enums;

enum UserRole: string {
    case Executor = 'executor';
    case Supervisor = 'supervisor';
    case Accountant = 'accountant';

    public function label(): string {
        return match ($this) {
            self::Executor => 'Ijrochi',
            self::Supervisor => 'Boshliq',
            self::Accountant => 'Buxgalter',
        };
    }
}
