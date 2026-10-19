<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Mtn = 'mtn';
    case Airtel = 'airtel';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Mtn => 'MTN MoMo',
            self::Airtel => 'Airtel Money',
            self::Card => 'Card',
        };
    }

    public function sitsInDrawer(): bool
    {
        return $this === self::Cash;
    }
}
