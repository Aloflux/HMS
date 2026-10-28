<?php

namespace App\Enums;

enum StockReason: string
{
    case Purchase = 'purchase';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Dispense = 'dispense';
    case WardIssue = 'ward_issue';
    case InternalUse = 'internal_use';
    case LabUse = 'lab_use';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Opening = 'opening';
    case Donation = 'donation';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Delivery received',
            self::TransferIn => 'Issued in from another store',
            self::TransferOut => 'Issued out to another store',
            self::Dispense => 'Dispensed to patient',
            self::WardIssue => 'Ward issue to patient',
            self::InternalUse => 'Department use',
            self::LabUse => 'Laboratory use',
            self::Return => 'Returned to main store',
            self::Adjustment => 'Adjustment',
            self::Opening => 'Opening balance',
            self::Donation => 'Donation received',
        };
    }

    /**
     * Reasons that mean the stock was used up, rather than moved between stores.
     *
     * @return list<self>
     */
    public static function consumption(): array
    {
        return [self::Dispense, self::WardIssue, self::InternalUse, self::LabUse];
    }
}
