<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Registered = 'registered';
    case Triaged = 'triaged';
    case Consulting = 'consulting';
    case Admitted = 'admitted';
    case Discharged = 'discharged';
    case Closed = 'closed';

    public function canMoveTo(self $status): bool
    {
        if ($this === $status) {
            return true;
        }

        return match ($this) {
            self::Registered => in_array($status, [self::Triaged, self::Consulting, self::Admitted, self::Discharged, self::Closed], true),
            self::Triaged => in_array($status, [self::Consulting, self::Admitted, self::Discharged, self::Closed], true),
            self::Consulting => in_array($status, [self::Admitted, self::Discharged, self::Closed], true),
            self::Admitted => $status === self::Discharged,
            self::Discharged => $status === self::Closed,
            self::Closed => false,
        };
    }

    public function label(): string
    {
        return str_replace('_', ' ', $this->value);
    }
}
