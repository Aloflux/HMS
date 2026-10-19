<?php

namespace App\Enums;

enum Priority: string
{
    case Routine = 'routine';
    case Urgent = 'urgent';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Routine',
            self::Urgent => 'Urgent',
            self::Emergency => 'Emergency',
        };
    }
}
