<?php

namespace App\Support;

use App\Models\User;

class Navigation
{
    /**
     * @return list<array{label: string, route: string, permission: string, pattern: string, icon: string}>
     */
    public static function items(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'dashboard.view', 'pattern' => 'dashboard', 'icon' => 'bx-pulse'],
            ['label' => 'Patients', 'route' => 'patients.index', 'permission' => 'patients.view', 'pattern' => 'patients.*', 'icon' => 'bx-user'],
            ['label' => 'Appointments', 'route' => 'appointments.index', 'permission' => 'appointments.manage', 'pattern' => 'appointments.*', 'icon' => 'bx-calendar'],
            ['label' => 'Queues', 'route' => 'queues.index', 'permission' => 'queue.view', 'pattern' => 'queues.*', 'icon' => 'bx-list-ol'],
            ['label' => 'Laboratory', 'route' => 'lab.index', 'permission' => 'lab.view', 'pattern' => 'lab.*', 'icon' => 'bx-test-tube'],
            ['label' => 'Radiology', 'route' => 'radiology.index', 'permission' => 'radiology.view', 'pattern' => 'radiology.*', 'icon' => 'bx-scan'],
            ['label' => 'Pharmacy', 'route' => 'pharmacy.index', 'permission' => 'pharmacy.view', 'pattern' => 'pharmacy.*', 'icon' => 'bx-capsule'],
            ['label' => 'Cashier', 'route' => 'cashier.index', 'permission' => 'cashier.manage', 'pattern' => 'cashier.*', 'icon' => 'bx-wallet'],
            ['label' => 'Insurance', 'route' => 'insurance.index', 'permission' => 'insurance.manage', 'pattern' => 'insurance.*', 'icon' => 'bx-shield-quarter'],
            ['label' => 'Wards', 'route' => 'wards.index', 'permission' => 'wards.view', 'pattern' => 'wards.*', 'icon' => 'bx-bed'],
            ['label' => 'Maternity', 'route' => 'maternity.index', 'permission' => 'maternity.manage', 'pattern' => 'maternity.*', 'icon' => 'bx-heart'],
            ['label' => 'Theatre', 'route' => 'theatre.index', 'permission' => 'theatre.manage', 'pattern' => 'theatre.*', 'icon' => 'bx-plus-medical'],
            ['label' => 'Inventory', 'route' => 'inventory.index', 'permission' => 'inventory.view', 'pattern' => 'inventory.*', 'icon' => 'bx-package'],
            ['label' => 'Reports', 'route' => 'reports.index', 'permission' => 'reports.view', 'pattern' => 'reports.*', 'icon' => 'bx-bar-chart-alt-2'],
            ['label' => 'Administration', 'route' => 'admin.users.index', 'permission' => 'admin.manage', 'pattern' => 'admin.*', 'icon' => 'bx-cog'],
        ];
    }

    /**
     * @return list<array{label: string, route: string, permission: string, pattern: string, icon: string}>
     */
    public static function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return array_values(array_filter(
            self::items(),
            fn (array $item): bool => $user->permits($item['permission']),
        ));
    }
}
