<?php

namespace App\Enums;

enum VisitType: string
{
    case Outpatient = 'outpatient';
    case Emergency = 'emergency';
    case Inpatient = 'inpatient';

    public function label(): string
    {
        return match ($this) {
            self::Outpatient => 'Outpatient',
            self::Emergency => 'Emergency',
            self::Inpatient => 'Inpatient',
        };
    }
}
