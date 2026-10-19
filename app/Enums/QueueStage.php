<?php

namespace App\Enums;

enum QueueStage: string
{
    case Triage = 'triage';
    case Consultation = 'consultation';
    case Laboratory = 'laboratory';
    case Radiology = 'radiology';
    case Pharmacy = 'pharmacy';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Triage => 'Triage',
            self::Consultation => 'Consultation',
            self::Laboratory => 'Laboratory',
            self::Radiology => 'Radiology',
            self::Pharmacy => 'Pharmacy',
            self::Cashier => 'Cashier',
        };
    }
}
