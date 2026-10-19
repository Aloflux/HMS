<?php

namespace App\Enums;

enum Role: string
{
    case FrontOffice = 'front_office';
    case Clinical = 'clinical';
    case Diagnostics = 'diagnostics';
    case PharmacyStores = 'pharmacy_stores';
    case Administrator = 'administrator';
    case PlatformAdmin = 'platform_admin';

    public function label(): string
    {
        return match ($this) {
            self::FrontOffice => 'Front office',
            self::Clinical => 'Clinical team',
            self::Diagnostics => 'Diagnostics',
            self::PharmacyStores => 'Pharmacy and stores',
            self::Administrator => 'Administrator',
            self::PlatformAdmin => 'Platform administrator',
        };
    }

    public function covers(): string
    {
        return match ($this) {
            self::FrontOffice => 'Reception, appointments, queues, and cashier',
            self::Clinical => 'Triage, consultation, wards, maternity, and theatre',
            self::Diagnostics => 'Laboratory and imaging',
            self::PharmacyStores => 'Dispensing, procurement, and stock',
            self::Administrator => 'Users, prices, insurance, reports, and approvals',
            self::PlatformAdmin => 'Opens and closes facilities',
        };
    }

    /**
     * @return list<self>
     */
    public static function facilityRoles(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role): bool => $role !== self::PlatformAdmin,
        ));
    }
}
