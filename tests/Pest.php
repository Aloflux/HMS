<?php

use App\Enums\Payer;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\VisitType;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\EncounterService;
use App\Services\FacilityProvisioner;
use App\Support\FacilityContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function provisionHospital(): array
{
    $facility = app(FacilityProvisioner::class)->provision([
        'name' => 'Test Hospital',
        'code' => 'TST'.fake()->unique()->numerify('###'),
        'kind' => 'hospital',
        'district' => 'Kampala',
        'bed_capacity' => 20,
    ], [
        'name' => 'Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);

    FacilityContext::forget();

    $admin = User::query()->where('facility_id', $facility->id)->where('role', Role::Administrator)->first();

    return [$facility, $admin];
}

function openCashVisit(Facility $facility, User $actor): Visit
{
    $patient = Patient::query()->create([
        'facility_id' => $facility->id,
        'patient_number' => 'TST-'.fake()->unique()->numerify('####'),
        'surname' => 'Okello',
        'given_name' => 'Peter',
        'sex' => 'male',
        'date_of_birth' => '1985-06-01',
        'phone' => fake()->unique()->numerify('0772######'),
        'district' => 'Kampala',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_relationship' => 'Brother',
        'next_of_kin_phone' => '0772000011',
        'consent_at' => now(),
    ]);

    FacilityContext::set($facility->id);
    $visit = app(EncounterService::class)->openVisit($patient, $actor, [
        'visit_type' => VisitType::Outpatient,
        'priority' => Priority::Routine,
        'payer' => Payer::Cash,
    ]);
    FacilityContext::forget();

    return $visit;
}
