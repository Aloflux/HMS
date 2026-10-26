<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Facility;
use App\Models\Service;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Ward;
use App\Support\FacilityContext;
use Illuminate\Support\Facades\DB;

class FacilityProvisioner
{
    /**
     * @param  array{name: string, code: string, kind: string, district: string, address?: ?string, phone?: ?string, email?: ?string, bed_capacity?: int, country?: string, moh_code?: ?string}  $attributes
     * @param  array{name: string, email: string, password: string, phone?: ?string}  $admin
     * @param  bool  $selfService  A facility that signed itself up: the owner must verify their email and finish setup first.
     */
    public function provision(array $attributes, array $admin, bool $selfService = false): Facility
    {
        return DB::transaction(function () use ($attributes, $admin, $selfService): Facility {
            $facility = Facility::query()->create([
                'name' => $attributes['name'],
                'code' => strtoupper($attributes['code']),
                'kind' => $attributes['kind'],
                'country' => $attributes['country'] ?? 'UG',
                'district' => $attributes['district'],
                'address' => $attributes['address'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                'email' => $attributes['email'] ?? null,
                'bed_capacity' => $attributes['bed_capacity'] ?? 0,
                'moh_code' => $attributes['moh_code'] ?? null,
                'is_active' => true,
                'onboarded_at' => $selfService ? null : now(),
            ]);

            FacilityContext::set($facility->id);

            $departments = [
                ['Outpatient', 'OPD', 'opd'],
                ['Emergency', 'EMR', 'emergency'],
                ['Laboratory', 'LAB', 'laboratory'],
                ['Radiology', 'RAD', 'radiology'],
                ['Pharmacy', 'PHARM', 'pharmacy'],
                ['Female ward', 'WF', 'ward'],
                ['Male ward', 'WM', 'ward'],
                ['Maternity', 'MAT', 'maternity'],
                ['Theatre', 'THR', 'theatre'],
                ['Main store', 'STORE', 'stores'],
                ['Cashier', 'CASH', 'cashier'],
                ['Administration', 'ADMIN', 'administration'],
            ];

            $departmentIds = [];

            foreach ($departments as [$name, $code, $kind]) {
                $department = Department::query()->create([
                    'facility_id' => $facility->id,
                    'name' => $name,
                    'code' => $code,
                    'kind' => $kind,
                    'is_active' => true,
                ]);
                $departmentIds[$code] = $department->id;
            }

            $locations = [
                ['Main store', 'main', 'STORE'],
                ['Pharmacy', 'pharmacy', 'PHARM'],
                ['Laboratory', 'laboratory', 'LAB'],
                ['Female ward store', 'ward', 'WF'],
                ['Male ward store', 'ward', 'WM'],
                ['Theatre store', 'theatre', 'THR'],
            ];

            foreach ($locations as [$name, $kind, $departmentCode]) {
                StockLocation::query()->create([
                    'facility_id' => $facility->id,
                    'name' => $name,
                    'kind' => $kind,
                    'department_id' => $departmentIds[$departmentCode],
                    'is_active' => true,
                ]);
            }

            foreach ([
                ['REG', 'Registration', 'registration', 5000],
                ['CONSULT', 'Consultation', 'consultation', 20000],
                ['BED', 'Bed day', 'bed', 40000],
            ] as [$code, $name, $category, $price]) {
                Service::query()->create([
                    'facility_id' => $facility->id,
                    'code' => $code,
                    'name' => $name,
                    'category' => $category,
                    'cash_price' => $price,
                    'is_active' => true,
                ]);
            }

            foreach ([['Female ward', 'WF', 4], ['Male ward', 'WM', 4]] as [$name, $code, $beds]) {
                $ward = Ward::query()->create([
                    'facility_id' => $facility->id,
                    'department_id' => $departmentIds[$code],
                    'name' => $name,
                    'code' => $code,
                ]);

                for ($number = 1; $number <= $beds; $number++) {
                    Bed::query()->create([
                        'facility_id' => $facility->id,
                        'ward_id' => $ward->id,
                        'code' => 'Bed '.$number,
                        'status' => 'available',
                    ]);
                }
            }

            User::query()->create([
                'name' => $admin['name'],
                'email' => $admin['email'],
                'password' => $admin['password'],
                'phone' => $admin['phone'] ?? null,
                'facility_id' => $facility->id,
                'role' => Role::Administrator,
                'email_verified_at' => $selfService ? null : now(),
                'is_active' => true,
            ]);

            return $facility;
        });
    }
}
