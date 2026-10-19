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
}
