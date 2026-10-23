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
     * Work areas an administrator can assign. Approvals stay with the administrator.
     *
     * @return list<array{key: string, label: string, detail: string, permissions: list<string>}>
     */
    public static function areas(): array
    {
        return [
            ['key' => 'reception', 'label' => 'Reception', 'detail' => 'Patients, appointments, the queue, and the cashier', 'permissions' => self::MAP['front_office']],
            ['key' => 'clinical', 'label' => 'Clinical', 'detail' => 'Triage, consultation, wards, maternity, and theatre', 'permissions' => self::MAP['clinical']],
            ['key' => 'laboratory', 'label' => 'Laboratory', 'detail' => 'Receive specimens and release results', 'permissions' => ['dashboard.view', 'patients.view', 'lab.view', 'lab.manage']],
            ['key' => 'imaging', 'label' => 'Imaging', 'detail' => 'Receive imaging orders and write reports', 'permissions' => ['dashboard.view', 'patients.view', 'radiology.view', 'radiology.manage']],
            ['key' => 'pharmacy', 'label' => 'Pharmacy', 'detail' => 'Dispense prescriptions', 'permissions' => ['dashboard.view', 'patients.view', 'pharmacy.view', 'pharmacy.manage']],
            ['key' => 'stores', 'label' => 'Stores', 'detail' => 'Stock, purchasing, and requisitions. Approval stays with the administrator', 'permissions' => ['dashboard.view', 'inventory.view', 'inventory.requisition', 'inventory.purchase', 'inventory.adjust']],
            ['key' => 'reports', 'label' => 'Reports', 'detail' => 'Facility reports', 'permissions' => ['dashboard.view', 'reports.view']],
        ];
    }

    /**
     * @return list<string>
     */
    public static function areaKeysForDepartmentKind(string $kind): array
    {
        return match ($kind) {
            'opd', 'emergency', 'ward', 'maternity', 'theatre' => ['clinical'],
            'laboratory' => ['laboratory'],
            'radiology' => ['imaging'],
            'pharmacy' => ['pharmacy'],
            'stores' => ['stores'],
            'cashier' => ['reception'],
            'administration' => ['reports'],
            default => [],
        };
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function permissionsForAreas(array $keys): array
    {
        $granted = [];

        foreach (self::areas() as $area) {
            if (in_array($area['key'], $keys, true)) {
                array_push($granted, ...$area['permissions']);
            }
        }

        return array_values(array_unique($granted));
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public static function areasMatching(array $permissions): array
    {
        if (in_array('*', $permissions, true)) {
            return [];
        }

        $keys = [];

        foreach (self::areas() as $area) {
            if (array_diff($area['permissions'], $permissions) === []) {
                $keys[] = $area['key'];
            }
        }

        return $keys;
    }

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
