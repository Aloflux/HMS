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

    /**
     * The permission a person needs to call and serve patients at this stage.
     */
    public function permission(): string
    {
        return match ($this) {
            self::Triage => 'triage.manage',
            self::Consultation => 'consultations.manage',
            self::Laboratory => 'lab.manage',
            self::Radiology => 'radiology.manage',
            self::Pharmacy => 'pharmacy.manage',
            self::Cashier => 'cashier.manage',
        };
    }

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
