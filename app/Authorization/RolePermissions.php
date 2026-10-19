<?php

namespace App\Authorization;

use App\Enums\Role;

class RolePermissions
{
    /**
     * @var array<string, list<string>>
     */
    private const MAP = [
        'front_office' => [
            'dashboard.view',
            'patients.view',
            'patients.manage',
            'appointments.manage',
            'queue.view',
            'billing.view',
            'cashier.manage',
        ],
        'clinical' => [
            'dashboard.view',
            'patients.view',
            'queue.view',
            'triage.manage',
            'consultations.manage',
            'lab.view',
            'radiology.view',
            'pharmacy.view',
            'wards.view',
            'wards.manage',
            'maternity.manage',
            'theatre.manage',
        ],
        'diagnostics' => [
            'dashboard.view',
            'patients.view',
            'lab.view',
            'lab.manage',
            'radiology.view',
            'radiology.manage',
        ],
        'pharmacy_stores' => [
            'dashboard.view',
            'patients.view',
            'pharmacy.view',
            'pharmacy.manage',
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
