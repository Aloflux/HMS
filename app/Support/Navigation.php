<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class Navigation
{
    /**
     * @return list<array{label: string, route: string, permission: string, pattern: string, icon: string, group: string|null}>
     */
    public static function items(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'permission' => 'dashboard.view', 'pattern' => 'dashboard', 'icon' => 'bx-home-alt', 'group' => null],
            ['label' => 'Patients', 'route' => 'patients.index', 'permission' => 'patients.view', 'pattern' => 'patients.*', 'icon' => 'bx-user', 'group' => null],
            ['label' => 'Appointments', 'route' => 'appointments.index', 'permission' => 'appointments.manage', 'pattern' => 'appointments.*', 'icon' => 'bx-calendar', 'group' => null],
            ['label' => 'Queues', 'route' => 'queues.index', 'permission' => 'queue.view', 'pattern' => 'queues.*', 'icon' => 'bx-list-ol', 'group' => 'Clinical'],
            ['label' => 'Laboratory', 'route' => 'lab.index', 'permission' => 'lab.view', 'pattern' => 'lab.*', 'icon' => 'bx-test-tube', 'group' => 'Clinical'],
            ['label' => 'Radiology', 'route' => 'radiology.index', 'permission' => 'radiology.view', 'pattern' => 'radiology.*', 'icon' => 'bx-scan', 'group' => 'Clinical'],
            ['label' => 'Wards', 'route' => 'wards.index', 'permission' => 'wards.view', 'pattern' => 'wards.*', 'icon' => 'bx-bed', 'group' => 'Clinical'],
            ['label' => 'Maternity', 'route' => 'maternity.index', 'permission' => 'maternity.manage', 'pattern' => 'maternity.*', 'icon' => 'bx-heart', 'group' => 'Clinical'],
            ['label' => 'Theatre', 'route' => 'theatre.index', 'permission' => 'theatre.manage', 'pattern' => 'theatre.*', 'icon' => 'bx-plus-medical', 'group' => 'Clinical'],
            ['label' => 'Pharmacy', 'route' => 'pharmacy.index', 'permission' => 'pharmacy.view', 'pattern' => 'pharmacy.*', 'icon' => 'bx-capsule', 'group' => 'Operations'],
            ['label' => 'Cashier', 'route' => 'cashier.index', 'permission' => 'cashier.manage', 'pattern' => 'cashier.*', 'icon' => 'bx-wallet', 'group' => 'Operations'],
            ['label' => 'Insurance', 'route' => 'insurance.index', 'permission' => 'insurance.manage', 'pattern' => 'insurance.*', 'icon' => 'bx-shield-quarter', 'group' => 'Operations'],
            ['label' => 'Inventory', 'route' => 'inventory.index', 'permission' => 'inventory.view', 'pattern' => 'inventory.*', 'icon' => 'bx-package', 'group' => 'Operations'],
            ['label' => 'Stock levels', 'route' => 'stock.levels', 'permission' => 'inventory.view', 'pattern' => 'stock.levels*', 'icon' => 'bx-layer', 'group' => 'Operations'],
            ['label' => 'Stock usage', 'route' => 'stock.usage', 'permission' => 'inventory.view', 'pattern' => 'stock.usage*', 'icon' => 'bx-line-chart-down', 'group' => 'Operations'],
            ['label' => 'Reports', 'route' => 'reports.index', 'permission' => 'reports.view', 'pattern' => 'reports.*', 'icon' => 'bx-bar-chart-alt-2', 'group' => 'Operations'],
            ['label' => 'Users', 'route' => 'admin.users.index', 'permission' => 'admin.manage', 'pattern' => 'admin.users.*', 'icon' => 'bx-group', 'group' => 'Administration'],
            ['label' => 'Departments', 'route' => 'admin.departments.index', 'permission' => 'admin.manage', 'pattern' => 'admin.departments.*', 'icon' => 'bx-sitemap', 'group' => 'Administration'],
            ['label' => 'Wards & beds', 'route' => 'admin.wards.index', 'permission' => 'admin.manage', 'pattern' => 'admin.wards.*', 'icon' => 'bx-building-house', 'group' => 'Administration'],
            ['label' => 'Price list', 'route' => 'admin.catalogue.index', 'permission' => 'admin.manage', 'pattern' => 'admin.catalogue.*', 'icon' => 'bx-purchase-tag', 'group' => 'Administration'],
            ['label' => 'Audit trail', 'route' => 'admin.audit.index', 'permission' => 'admin.manage', 'pattern' => 'admin.audit.*', 'icon' => 'bx-history', 'group' => 'Administration'],
        ];
    }

    /**
     * The browser tab title for a route, taken from the sidebar label that owns it.
     */
    public static function titleFor(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        $extra = [
            ['pattern' => 'visits.*', 'label' => 'Visit'],
            ['pattern' => 'receipts.*', 'label' => 'Receipt'],
            ['pattern' => 'platform.*', 'label' => 'Facilities'],
            ['pattern' => 'profile.*', 'label' => 'Profile'],
        ];

        foreach ([...self::items(), ...$extra] as $item) {
            if (Str::is($item['pattern'], $routeName)) {
                return $item['label'];
            }
        }

        return null;
    }

    /**
     * @return list<array{label: string, route: string, permission: string, pattern: string, icon: string, group: string|null}>
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

    /**
     * @return array{primary: list<array{label: string, route: string, permission: string, pattern: string, icon: string, group: string|null}>, groups: list<array{label: string, items: list<array{label: string, route: string, permission: string, pattern: string, icon: string, group: string|null}>}>, footer: list<array{label: string, route: string, permission: string, pattern: string, icon: string, group: string|null}>}
     */
    public static function sections(?User $user): array
    {
        $primary = [];
        $grouped = [];
        $footer = [];

        foreach (self::forUser($user) as $item) {
            if ($item['group'] === null) {
                $primary[] = $item;
            } elseif ($item['group'] === 'Account') {
                $footer[] = $item;
            } else {
                $grouped[$item['group']][] = $item;
            }
        }

        $groups = [];

        foreach ($grouped as $label => $items) {
            $groups[] = ['label' => $label, 'items' => $items];
        }

        return [
            'primary' => $primary,
            'groups' => $groups,
            'footer' => $footer,
        ];
    }
}
