<?php

namespace Database\Seeders;

use App\Authorization\RolePermissions;
use App\Enums\Role;
use App\Models\Department;
use App\Models\Facility;
use App\Models\Membership;
use App\Models\User;
use App\Services\FacilityProvisioner;
use App\Support\FacilityContext;
use Illuminate\Database\Seeder;

/**
 * A second facility and people who work at both, each signing in with one email:
 * one who chooses a facility at sign-in, one switched off at one of them, and one invited but not yet joined.
 * Runs after DemoSeeder, which creates St. Mary's. Both skip themselves when their facility already exists, so seeding can be rerun.
 */
class MultiFacilitySeeder extends Seeder
{
    public function run(): void
    {
        if (Facility::query()->where('code', 'GFH')->exists()) {
            $this->command?->warn('Gulu Family Health Clinic is already seeded. Skipping MultiFacilitySeeder.');

            return;
        }

        $stMarys = Facility::query()->where('code', 'SMH')->firstOrFail();

        $gulu = app(FacilityProvisioner::class)->provision([
            'name' => 'Gulu Family Health Clinic',
            'code' => 'GFH',
            'kind' => 'clinic',
            'district' => 'Gulu',
            'address' => 'Awich Road',
            'phone' => '0471000002',
            'bed_capacity' => 8,
        ], [
            'name' => 'Okello Denis',
            'email' => 'admin@gulufamily.tunzuri.test',
            'password' => 'password',
        ]);

        FacilityContext::forget();

        $clinician = $this->person('Dr. Grace Atim', 'grace.atim@tunzuri.test');
        $this->join($clinician, $stMarys, Role::Staff, ['clinical'], 'OPD');
        $this->join($clinician, $gulu, Role::Administrator);

        $locum = $this->person('Brian Mugisha', 'brian.mugisha@tunzuri.test');
        $this->join($locum, $stMarys, Role::Staff, ['laboratory'], 'LAB', isActive: false);
        $this->join($locum, $gulu, Role::Staff, ['laboratory', 'reception'], 'LAB');

        $guluNurse = $this->person('Agnes Lamunu', 'agnes.lamunu@tunzuri.test');
        $this->join($guluNurse, $gulu, Role::Staff, ['clinical'], 'WF');
        $this->invite($guluNurse, $stMarys, 'Agnes L.', ['clinical'], 'WF');
    }

    private function person(string $name, string $email): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $areas
     */
    private function join(User $person, Facility $facility, Role $role, array $areas = [], ?string $departmentCode = null, bool $isActive = true): Membership
    {
        return $person->memberships()->create([
            ...$this->access($facility, $role, $areas, $departmentCode),
            'is_active' => $isActive,
            'accepted_at' => now(),
            'last_login_at' => $isActive ? now()->subDays(random_int(0, 6)) : now()->subMonths(2),
        ]);
    }

    /**
     * An invitation the person has not accepted, as an administrator would leave it. No email is sent.
     *
     * @param  list<string>  $areas
     */
    private function invite(User $person, Facility $facility, string $typedName, array $areas, string $departmentCode): void
    {
        $membership = $person->memberships()->create([
            ...$this->access($facility, Role::Staff, $areas, $departmentCode),
            'is_active' => true,
            'invited_name' => $typedName,
        ]);

        $membership->issueInvitation($facility->firstAdministrator());
    }

    /**
     * @param  list<string>  $areas
     * @return array{facility_id: int, role: Role, permissions: list<string>|null, department_id: int|null}
     */
    private function access(Facility $facility, Role $role, array $areas, ?string $departmentCode): array
    {
        return [
            'facility_id' => $facility->id,
            'role' => $role,
            'permissions' => $role === Role::Administrator ? null : RolePermissions::permissionsForAreas($areas),
            'department_id' => $departmentCode === null ? null : Department::query()
                ->where('facility_id', $facility->id)
                ->where('code', $departmentCode)
                ->value('id'),
        ];
    }
}
