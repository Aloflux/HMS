<?php

use App\Enums\BillingType;
use App\Enums\Payer;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\VisitType;
use App\Models\Bed;
use App\Models\BillLine;
use App\Models\Item;
use App\Models\Patient;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\EncounterService;
use App\Services\FacilityProvisioner;
use App\Services\PatientRegistrar;
use App\Services\WardStockService;
use App\Support\FacilityContext;
use Database\Seeders\Icd10Seeder;

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

test('sample accounts can be clicked into the login form when the demo flag is on', function () {
    config(['hms.show_demo_login' => true, 'hms.demo_password' => 'password']);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-demo-email="doctor@stmarys.afyadesk.test"', false)
        ->assertSee('data-demo-password="password"', false);
});

test('sample accounts stay hidden when the demo flag is off', function () {
    config(['hms.show_demo_login' => false]);

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('data-demo-email', false);
});

test('front office cannot open a consultation', function () {
    [$facility] = provisionHospital();
    $clerk = User::factory()->create([
        'facility_id' => $facility->id,
        'role' => Role::FrontOffice,
    ]);
    $patient = Patient::query()->create([
        'facility_id' => $facility->id,
        'patient_number' => 'TST-1',
        'surname' => 'Achieng',
        'given_name' => 'Mary',
        'sex' => 'female',
        'date_of_birth' => '1990-01-01',
        'phone' => '0772000009',
        'district' => 'Kampala',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_relationship' => 'Sister',
        'next_of_kin_phone' => '0772000010',
        'consent_at' => now(),
    ]);
    FacilityContext::set($facility->id);
    $visit = app(EncounterService::class)->openVisit($patient, $clerk, [
        'visit_type' => VisitType::Outpatient,
        'priority' => Priority::Routine,
        'payer' => Payer::Cash,
    ]);
    FacilityContext::forget();

    $this->actingAs($clerk)
        ->post(route('visits.consult', $visit), [
            'icd10_codes' => ['J06.9'],
        ])
        ->assertForbidden();
});

test('another facility cannot see this facility patient', function () {
    [$facility, $admin] = provisionHospital();
    [$other] = provisionHospital();

    $patient = Patient::query()->create([
        'facility_id' => $other->id,
        'patient_number' => 'OTHER-1',
        'surname' => 'Hidden',
        'given_name' => 'Patient',
        'sex' => 'male',
        'date_of_birth' => '1980-01-01',
        'phone' => '0700000099',
        'district' => 'Gulu',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_relationship' => 'Brother',
        'next_of_kin_phone' => '0700000098',
        'consent_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('patients.show', $patient))
        ->assertNotFound();

    expect(Patient::query()->where('facility_id', $facility->id)->count())->toBe(0);
});

test('prescribing does not move stock and dispensing posts a charge', function () {
    $this->seed(Icd10Seeder::class);
    [$facility, $admin] = provisionHospital();

    $item = Item::query()->create([
        'facility_id' => $facility->id,
        'sku' => 'PARA',
        'name' => 'Paracetamol',
        'category' => 'medicine',
        'billing_type' => BillingType::BillableMedicine,
        'unit' => 'tablet',
        'cash_price' => 500,
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    $pharmacy = StockLocation::query()->where('facility_id', $facility->id)->where('kind', 'pharmacy')->first();
    StockBatch::query()->create([
        'facility_id' => $facility->id,
        'item_id' => $item->id,
        'stock_location_id' => $pharmacy->id,
        'batch_number' => 'B1',
        'expiry_date' => now()->addYear()->toDateString(),
        'unit_cost' => 200,
        'quantity' => 10,
    ]);

    FacilityContext::set($facility->id);
    $patient = app(PatientRegistrar::class)->register($facility, $admin, [
        'surname' => 'Nakato',
        'given_name' => 'Amina',
        'sex' => 'female',
        'date_of_birth' => '1994-04-12',
        'phone' => '0772111222',
        'district' => 'Kampala',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_relationship' => 'Sister',
        'next_of_kin_phone' => '0772111223',
    ]);
    $visit = app(EncounterService::class)->openVisit($patient, $admin, [
        'visit_type' => VisitType::Outpatient,
        'priority' => Priority::Routine,
        'payer' => Payer::Cash,
    ]);
    $prescription = app(EncounterService::class)->prescribe($visit, $admin, [[
        'item_id' => $item->id,
        'dose' => '1g',
        'frequency' => 'TDS',
        'duration_days' => 3,
        'quantity' => 6,
    ]]);
    FacilityContext::forget();

    expect(StockBatch::query()->where('batch_number', 'B1')->value('quantity'))->toBe(10);

    $pharmacist = User::factory()->create([
        'facility_id' => $facility->id,
        'role' => Role::PharmacyStores,
    ]);

    $this->actingAs($pharmacist)
        ->post(route('pharmacy.dispense', $prescription->items()->first()), [
            'quantity' => 4,
        ])
        ->assertRedirect();

    expect(StockBatch::query()->where('batch_number', 'B1')->value('quantity'))->toBe(6);
    $charge = BillLine::query()->where('source_type', 'dispensation')->first();
    expect($charge)->not->toBeNull();
    expect($charge->amount)->toBe(2000);
});

test('a repeated registration is blocked until the clerk confirms it', function () {
    [$facility, $admin] = provisionHospital();

    $payload = [
        'surname' => 'Okello',
        'given_name' => 'John',
        'sex' => 'male',
        'date_of_birth' => '1988-11-02',
        'phone' => '0703000002',
        'district' => 'Wakiso',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_relationship' => 'Brother',
        'next_of_kin_phone' => '0703000003',
        'consent' => '1',
    ];

    $this->actingAs($admin)->post(route('patients.store'), $payload)->assertRedirect();

    $this->actingAs($admin)
        ->from(route('patients.create'))
        ->post(route('patients.store'), $payload)
        ->assertRedirect(route('patients.create'))
        ->assertSessionHasErrors('confirm_duplicate');

    expect(Patient::query()->where('facility_id', $facility->id)->count())->toBe(1);
});

test('non billable ward stock is a department cost and not a patient charge', function () {
    [$facility, $admin] = provisionHospital();
    $item = Item::query()->create([
        'facility_id' => $facility->id,
        'sku' => 'GLOVE',
        'name' => 'Gloves',
        'category' => 'consumable',
        'billing_type' => BillingType::NonBillable,
        'unit' => 'pair',
        'cash_price' => 0,
        'reorder_level' => 0,
        'is_active' => true,
    ]);
    $location = StockLocation::query()->where('facility_id', $facility->id)->where('kind', 'ward')->first();
    StockBatch::query()->create([
        'facility_id' => $facility->id,
        'item_id' => $item->id,
        'stock_location_id' => $location->id,
        'batch_number' => 'G1',
        'unit_cost' => 100,
        'quantity' => 8,
    ]);

    FacilityContext::set($facility->id);
    $patient = app(PatientRegistrar::class)->register($facility, $admin, [
        'surname' => 'Adeke',
        'given_name' => 'Grace',
        'sex' => 'female',
        'date_of_birth' => '1992-02-02',
        'phone' => '0755000001',
        'district' => 'Jinja',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_relationship' => 'Mother',
        'next_of_kin_phone' => '0755000002',
    ]);
    $visit = app(EncounterService::class)->openVisit($patient, $admin, [
        'visit_type' => VisitType::Emergency,
        'priority' => Priority::Emergency,
        'payer' => Payer::Cash,
    ]);
    $bed = Bed::query()->where('facility_id', $facility->id)->where('status', 'available')->first();
    $admission = app(EncounterService::class)->admit($visit, $admin, $bed, 'Malaria');
    $linesBefore = BillLine::query()->count();
    app(WardStockService::class)->issue($admission, $item, $location, 2, $admin);
    FacilityContext::forget();

    expect(StockBatch::query()->where('batch_number', 'G1')->value('quantity'))->toBe(6);
    expect(BillLine::query()->count())->toBe($linesBefore);
});

test('an inactive account cannot sign in', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});
