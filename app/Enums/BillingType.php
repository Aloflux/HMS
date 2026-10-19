<?php

namespace App\Enums;

enum BillingType: string
{
    case BillableMedicine = 'billable_medicine';
    case BillableConsumable = 'billable_consumable';
    case NonBillable = 'non_billable';

    public function label(): string
    {
        return match ($this) {
            self::BillableMedicine => 'Billable medicine',
            self::BillableConsumable => 'Billable consumable',
            self::NonBillable => 'Non-billable consumable',
        };
    }

    public function chargesPatient(): bool
    {
        return $this !== self::NonBillable;
    }
}
