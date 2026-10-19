<?php

namespace App\Authorization;

use App\Enums\Role;

class RolePermissions
{
    /**
     * @var array<string, list<string>>
     */
    private const MAP = [
        'receptionist' => [
            'dashboard.view',
            'patients.view',
            'patients.manage',
            'appointments.manage',
            'queue.view',
            'billing.view',
        ],
        'triage_nurse' => [
            'dashboard.view',
            'patients.view',
            'queue.view',
            'triage.manage',
        ],
        'clinician' => [
            'dashboard.view',
            'patients.view',
            'queue.view',
            'consultations.manage',
            'lab.view',
            'radiology.view',
            'pharmacy.view',
            'wards.view',
            'wards.manage',
            'maternity.manage',
            'theatre.manage',
        ],
        'ward_nurse' => [
            'dashboard.view',
            'patients.view',
            'wards.view',
            'wards.manage',
            'pharmacy.view',
        ],
        'lab_technician' => [
            'dashboard.view',
            'patients.view',
            'lab.view',
            'lab.manage',
        ],
        'radiographer' => [
            'dashboard.view',
            'patients.view',
            'radiology.view',
            'radiology.manage',
        ],
        'pharmacist' => [
            'dashboard.view',
            'patients.view',
            'pharmacy.view',
            'pharmacy.manage',
            'inventory.view',
            'inventory.requisition',
        ],
        'cashier' => [
            'dashboard.view',
            'patients.view',
            'billing.view',
            'cashier.manage',
            'queue.view',
        ],
        'claims_officer' => [
            'dashboard.view',
            'patients.view',
            'billing.view',
            'insurance.manage',
        ],
        'store_keeper' => [
            'dashboard.view',
            'inventory.view',
            'inventory.requisition',
            'inventory.purchase',
            'inventory.adjust',
        ],
        'administrator' => ['*'],
    ];

    /**
     * @return list<string>
     */
    public static function for(Role $role): array
    {
        return self::MAP[$role->value] ?? [];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        $names = ['platform.manage', 'reports.view', 'admin.manage', 'waivers.approve', 'inventory.approve'];

        foreach (self::MAP as $permissions) {
            foreach ($permissions as $permission) {
                if ($permission !== '*') {
                    $names[] = $permission;
                }
            }
        }

        return array_values(array_unique($names));
    }
}
