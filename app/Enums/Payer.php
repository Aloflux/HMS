<?php

namespace App\Enums;

enum Payer: string
{
    case Cash = 'cash';
    case Insurance = 'insurance';
    case Corporate = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Insurance => 'Insurance',
            self::Corporate => 'Corporate account',
        };
    }

    public function settlesAtCashier(): bool
    {
        return $this === self::Cash;
    }
}
