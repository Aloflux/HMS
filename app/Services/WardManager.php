<?php

namespace App\Services;

use App\Models\Bed;
use App\Models\StockLocation;
use App\Models\Ward;

class WardManager
{
    /**
     * Create a ward with its starting beds and, optionally, a ward stock store.
     */
    public function create(int $facilityId, string $name, string $code, ?int $departmentId, int $beds, bool $withStore): Ward
    {
        $ward = Ward::query()->create([
            'facility_id' => $facilityId,
            'department_id' => $departmentId,
            'name' => $name,
            'code' => strtoupper($code),
        ]);

        $this->addBeds($ward, $beds);

        $storeName = $ward->name.' store';

        if ($withStore && ! StockLocation::query()->where('facility_id', $facilityId)->where('name', $storeName)->exists()) {
            StockLocation::query()->create([
                'facility_id' => $facilityId,
                'name' => $storeName,
                'kind' => 'ward',
                'department_id' => $departmentId,
                'is_active' => true,
            ]);
        }

        return $ward;
    }

    /**
     * Add beds named "Bed N", numbered after the highest number already used in the ward.
     */
    public function addBeds(Ward $ward, int $count): int
    {
        $highest = $ward->beds()
            ->lockForUpdate()
            ->pluck('code')
            ->map(fn (string $code): int => preg_match('/(\d+)$/', $code, $matches) ? (int) $matches[1] : 0)
            ->max() ?? 0;

        for ($number = $highest + 1; $number <= $highest + $count; $number++) {
            $ward->beds()->create([
                'facility_id' => $ward->facility_id,
                'code' => 'Bed '.$number,
                'status' => 'available',
            ]);
        }

        return $count;
    }

    /**
     * Remove up to $count of the most recently added beds that are free and have never held a patient.
     */
    public function removeUnusedBeds(Ward $ward, int $count): int
    {
        $removable = $ward->beds()
            ->where('status', '!=', 'occupied')
            ->whereDoesntHave('admissions')
            ->orderByDesc('id')
            ->limit($count)
            ->get();

        $removable->each(fn (Bed $bed) => $bed->delete());

        return $removable->count();
    }
}
