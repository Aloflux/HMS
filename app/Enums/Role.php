<?php

namespace App\Enums;

enum Role: string
{
    case Receptionist = 'receptionist';
    case TriageNurse = 'triage_nurse';
    case Clinician = 'clinician';
    case WardNurse = 'ward_nurse';
    case LabTechnician = 'lab_technician';
    case Radiographer = 'radiographer';
    case Pharmacist = 'pharmacist';
    case Cashier = 'cashier';
    case ClaimsOfficer = 'claims_officer';
    case StoreKeeper = 'store_keeper';
    case Administrator = 'administrator';
    case PlatformAdmin = 'platform_admin';

    public function label(): string
    {
        return match ($this) {
            self::Receptionist => 'Receptionist',
            self::TriageNurse => 'Triage nurse',
            self::Clinician => 'Doctor / clinical officer',
            self::WardNurse => 'Ward nurse',
            self::LabTechnician => 'Lab technician',
            self::Radiographer => 'Radiographer',
            self::Pharmacist => 'Pharmacist',
            self::Cashier => 'Cashier',
            self::ClaimsOfficer => 'Insurance / claims officer',
            self::StoreKeeper => 'Store keeper',
            self::Administrator => 'Administrator',
            self::PlatformAdmin => 'Platform administrator',
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
