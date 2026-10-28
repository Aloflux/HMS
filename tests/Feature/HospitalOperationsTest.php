<?php

use App\Enums\BillingType;
use App\Enums\Payer;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\VisitType;
use App\Models\Bed;
use App\Models\Bill;
use App\Models\BillLine;
use App\Models\Item;
use App\Models\LabRequestItem;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Visit;
use App\Models\Waiver;
use App\Services\CashierService;
use App\Services\EncounterService;
use App\Services\PatientRegistrar;
use App\Services\WardStockService;
use App\Support\FacilityContext;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Icd10Seeder;
use Illuminate\Support\Facades\Hash;

test('sample accounts are the database users that still use the shared password', function (string $environment) {
    $this->app->detectEnvironment(fn () => $environment);
    config(['hms.demo_password' => 'password']);

    $platform = User::factory()->platformAdmin()->create([
        'name' => 'Platform Operator',
        'email' => 'platform@tunzuri.test',
    ]);
    [$facility, $admin] = provisionHospital();
    $changed = User::factory()->create([
        'facility_id' => $facility->id,
        'name' => 'Changed Clerk',
        'email' => 'changed@example.test',
        'password' => 'not-the-demo-password',
    ]);
    $inactive = User::factory()->create([
        'facility_id' => $facility->id,
        'name' => 'Inactive Clerk',
        'email' => 'inactive@example.test',
        'is_active' => false,
    ]);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Sample accounts', false)
        ->assertSee($platform->name, false)
        ->assertSee('data-demo-email="'.$platform->email.'"', false)
        ->assertSee($admin->name, false)
        ->assertSee('data-demo-email="'.$admin->email.'"', false)
        ->assertSee('data-demo-password="password"', false)
        ->assertDontSee('data-demo-email="'.$changed->email.'"', false)
        ->assertDontSee('data-demo-email="'.$inactive->email.'"', false);
})->with(['local', 'development']);

test('sample accounts are hidden when none still use the shared password', function () {
    $this->app->detectEnvironment(fn () => 'local');
    config(['hms.demo_password' => 'password']);

    User::factory()->create([
        'email' => 'changed@example.test',
        'password' => 'not-the-demo-password',
    ]);

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('Sample accounts', false)
        ->assertDontSee('data-demo-email', false);
});

test('sample accounts are hidden in production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    User::factory()->platformAdmin()->create([
        'email' => 'platform@tunzuri.test',
    ]);

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('Sample accounts', false)
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

test('front office can register a patient while booking the appointment', function () {
    [$facility] = provisionHospital();
    $clerk = User::factory()->create([
        'facility_id' => $facility->id,
        'role' => Role::FrontOffice,
    ]);
    $departmentId = $facility->departments()->value('id');

    $this->actingAs($clerk)
        ->post(route('appointments.store'), [
            'booking_for' => 'new',
            'surname' => 'Nambi',
            'given_name' => 'Grace',
            'sex' => 'female',
            'date_of_birth' => '1996-03-14',
            'phone' => '0772555666',
            'district' => 'Kampala',
            'next_of_kin_name' => 'Sara',
            'next_of_kin_relationship' => 'Sister',
            'next_of_kin_phone' => '0772555667',
            'consent' => '1',
            'department_id' => $departmentId,
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'reason' => 'Antenatal review',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $patient = Patient::query()->where('phone', '0772555666')->first();

    expect($patient)->not->toBeNull()
        ->and($patient->patient_number)->not->toBeEmpty();

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'department_id' => $departmentId,
        'status' => 'booked',
        'reason' => 'Antenatal review',
    ]);
});

test('front office can book an appointment for a patient already on the register', function () {
    [$facility] = provisionHospital();
    $clerk = User::factory()->create([
        'facility_id' => $facility->id,
        'role' => Role::FrontOffice,
    ]);
    $patient = Patient::query()->create([
        'facility_id' => $facility->id,
        'patient_number' => 'TST-2',
        'surname' => 'Okello',
        'given_name' => 'John',
        'sex' => 'male',
        'date_of_birth' => '1988-11-02',
        'phone' => '0703000002',
        'district' => 'Wakiso',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_relationship' => 'Brother',
        'next_of_kin_phone' => '0703000003',
        'consent_at' => now(),
    ]);

    $this->actingAs($clerk)
        ->post(route('appointments.store'), [
            'booking_for' => 'existing',
            'patient_id' => $patient->id,
            'department_id' => $facility->departments()->value('id'),
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'reason' => 'Review',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Appointment booked and reminder queued.');

    expect(Patient::query()->where('facility_id', $facility->id)->count())->toBe(1);
    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'status' => 'booked',
    ]);
});

test('booking a new patient asks for the registration details', function () {
    [$facility] = provisionHospital();
    $clerk = User::factory()->create([
        'facility_id' => $facility->id,
        'role' => Role::FrontOffice,
    ]);

    $this->actingAs($clerk)
        ->from(route('appointments.index'))
        ->post(route('appointments.store'), [
            'booking_for' => 'new',
            'department_id' => $facility->departments()->value('id'),
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect(route('appointments.index'))
        ->assertSessionHasErrors(['surname', 'phone', 'consent']);

    expect(Patient::query()->where('facility_id', $facility->id)->count())->toBe(0);
});

test('front office sees the facility overview', function () {
    [$facility] = provisionHospital();
    $clerk = User::factory()->create([
        'facility_id' => $facility->id,
        'role' => Role::FrontOffice,
    ]);

    $this->actingAs($clerk)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Facility overview')
        ->assertSee($facility->name)
        ->assertSee('Clinical')
        ->assertSee('Queues');
});

test('the patient directory can show only current inpatients', function () {
    [$facility] = provisionHospital();
    $clerk = User::factory()->create([
        'facility_id' => $facility->id,
        'role' => Role::FrontOffice,
    ]);
    $admitted = Patient::query()->create([
        'facility_id' => $facility->id,
        'patient_number' => 'ADM-1',
        'surname' => 'Akello',
        'given_name' => 'Grace',
        'sex' => 'female',
        'date_of_birth' => '1991-02-02',
        'phone' => '0772000091',
        'district' => 'Kampala',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_relationship' => 'Sister',
        'next_of_kin_phone' => '0772000092',
        'consent_at' => now(),
    ]);
    Patient::query()->create([
        'facility_id' => $facility->id,
        'patient_number' => 'OPD-1',
        'surname' => 'Walker',
        'given_name' => 'Peter',
        'sex' => 'male',
        'date_of_birth' => '1985-05-05',
        'phone' => '0772000093',
        'district' => 'Wakiso',
        'next_of_kin_name' => 'Kin',
        'next_of_kin_relationship' => 'Brother',
        'next_of_kin_phone' => '0772000094',
        'consent_at' => now(),
    ]);
    Visit::query()->create([
        'facility_id' => $facility->id,
        'patient_id' => $admitted->id,
        'visit_number' => 'V-ADM',
        'visit_type' => 'inpatient',
        'status' => 'admitted',
        'payer' => 'cash',
        'opened_at' => now(),
    ]);

    $this->actingAs($clerk)
        ->get(route('patients.index', ['care' => 'inpatient']))
        ->assertOk()
        ->assertSee('Akello')
        ->assertDontSee('Walker');
});

test('an administrator can open only the laboratory for a new user', function () {
    [$facility, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Lab Tech',
            'email' => 'labtech@example.com',
            'password' => 'password',
            'account' => 'staff',
            'areas' => ['laboratory'],
            'department_id' => $facility->departments()->where('kind', 'laboratory')->value('id'),
        ])
        ->assertRedirect();

    $user = User::query()->where('email', 'labtech@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(Role::Staff)
        ->and($user->permits('lab.manage'))->toBeTrue()
        ->and($user->permits('cashier.manage'))->toBeFalse()
        ->and($user->permits('admin.manage'))->toBeFalse();

    $this->actingAs($user)
        ->get(route('cashier.index'))
        ->assertForbidden();
});

test('an administrator cannot remove their own full access', function () {
    [$facility, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'account' => 'staff',
            'areas' => ['reports'],
            'is_active' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($admin->fresh()->role)->toBe(Role::Administrator);
});

test('an inactive account cannot sign in', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('a cancelled lab test cannot be verified', function () {
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);
    $service = Service::query()->create([
        'facility_id' => $facility->id,
        'code' => 'BS',
        'name' => 'Malaria smear',
        'category' => 'laboratory',
        'cash_price' => 3000,
        'is_active' => true,
    ]);
    $test = LabTest::query()->create([
        'facility_id' => $facility->id,
        'service_id' => $service->id,
        'code' => 'BS',
        'name' => 'Malaria smear',
        'is_active' => true,
    ]);

    FacilityContext::set($facility->id);
    $item = app(EncounterService::class)->orderLabs($visit, $admin, [$test->id])->items()->first();
    FacilityContext::forget();

    $this->actingAs($admin)->post(route('lab.cancel', $item))->assertRedirect();

    $this->actingAs($admin)
        ->post(route('lab.verify', $item), ['result_value' => 'Negative'])
        ->assertSessionHasErrors('result_value');

    expect(LabRequestItem::query()->find($item->id))
        ->status->toBe('cancelled')
        ->result_value->toBeNull();
});

test('the cashier cannot take more than the bill still owes', function () {
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);
    $bill = Bill::query()->where('visit_id', $visit->id)->first();

    $this->actingAs($admin)->post(route('cashier.shift'), ['opening_float' => 0]);

    $this->actingAs($admin)
        ->post(route('cashier.pay', $bill), ['method' => 'cash', 'amount' => 6000])
        ->assertSessionHasErrors('amount');
    expect(Payment::query()->count())->toBe(0);

    $this->actingAs($admin)
        ->post(route('cashier.pay', $bill), ['method' => 'cash', 'amount' => 5000])
        ->assertRedirect();
    expect($bill->fresh()->status)->toBe('paid');

    $this->actingAs($admin)
        ->post(route('cashier.pay', $bill), ['method' => 'cash', 'amount' => 5000])
        ->assertSessionHasErrors('amount');
    expect(Payment::query()->sum('amount'))->toEqual(5000);
});

test('a waiver larger than the remaining balance cannot be approved', function () {
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);
    $bill = Bill::query()->where('visit_id', $visit->id)->first();
    $waiver = app(CashierService::class)->requestWaiver($bill, $admin, 5000, 'Cannot afford');

    $this->actingAs($admin)->post(route('cashier.shift'), ['opening_float' => 0]);
    $this->actingAs($admin)->post(route('cashier.pay', $bill), ['method' => 'cash', 'amount' => 2000]);

    $this->actingAs($admin)
        ->post(route('waivers.approve', $waiver))
        ->assertSessionHasErrors('waiver');

    expect(Waiver::query()->find($waiver->id)->status)->toBe('pending');
    expect((int) $bill->fresh()->waived_amount)->toBe(0);
});

test('a closed visit cannot take new orders', function () {
    $this->seed(Icd10Seeder::class);
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);
    $bill = Bill::query()->where('visit_id', $visit->id)->first();

    $this->actingAs($admin)->post(route('visits.triage', $visit), [
        'priority' => 'routine',
        'chief_complaint' => 'Headache',
    ]);
    $this->actingAs($admin)->post(route('cashier.shift'), ['opening_float' => 0]);
    $this->actingAs($admin)->post(route('cashier.pay', $bill), ['method' => 'cash', 'amount' => 5000]);
    $this->actingAs($admin)->post(route('visits.complete', $visit))->assertSessionHasNoErrors();
    expect($visit->fresh()->status->value)->toBe('closed');

    $this->actingAs($admin)
        ->post(route('visits.consult', $visit), ['icd10_codes' => ['J06.9']])
        ->assertSessionHasErrors('visit');

    expect(BillLine::query()->where('source_type', 'consultation')->exists())->toBeFalse();
    expect($bill->fresh()->status)->toBe('paid');
});

test('the seeder creates only a platform administrator and one facility administrator', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->orderBy('email')->pluck('role', 'email')->map->value->all())->toBe([
        'admin@stmarys.tunzuri.test' => 'administrator',
        'platform@tunzuri.test' => 'platform_admin',
    ]);
    expect(Patient::query()->count())->toBeGreaterThan(0);
});

test('administrators reach users and departments from the sidebar', function () {
    [, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->get(route('admin.departments.index'))
        ->assertOk()
        ->assertSee(route('admin.users.index'), false)
        ->assertSee('Outpatient');
});

test('the platform administrator can add another administrator to a facility', function () {
    [$facility] = provisionHospital();
    $operator = User::factory()->create(['role' => Role::PlatformAdmin, 'facility_id' => null]);

    $this->actingAs($operator)
        ->get(route('platform.facilities.admins.index', $facility))
        ->assertOk();

    $this->actingAs($operator)
        ->post(route('platform.facilities.admins.store', $facility), [
            'name' => 'Second Admin',
            'email' => 'second@example.test',
            'password' => 'a-strong-password',
        ])
        ->assertRedirect();

    $added = User::query()->where('email', 'second@example.test')->first();
    expect($added->facility_id)->toBe($facility->id);
    expect($added->role)->toBe(Role::Administrator);
});

test('the platform administrator can reset an administrator password and turn them off', function () {
    [$facility, $admin] = provisionHospital();
    $operator = User::factory()->create(['role' => Role::PlatformAdmin, 'facility_id' => null]);
    User::factory()->create(['facility_id' => $facility->id, 'role' => Role::Administrator]);

    $this->actingAs($operator)
        ->patch(route('platform.facilities.admins.update', [$facility, $admin]), [
            'is_active' => '0',
            'password' => 'a-new-strong-password',
        ])
        ->assertSessionHas('status');

    $admin->refresh();
    expect($admin->is_active)->toBeFalse();
    expect(Hash::check('a-new-strong-password', $admin->password))->toBeTrue();
});

test('a facility always keeps one active administrator', function () {
    [$facility, $admin] = provisionHospital();
    $operator = User::factory()->create(['role' => Role::PlatformAdmin, 'facility_id' => null]);

    $this->actingAs($operator)
        ->patch(route('platform.facilities.admins.update', [$facility, $admin]), ['is_active' => '0'])
        ->assertSessionHas('error');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('the platform administrator cannot change facility staff or another facility admin', function () {
    [$facility] = provisionHospital();
    [, $otherAdmin] = provisionHospital();
    $operator = User::factory()->create(['role' => Role::PlatformAdmin, 'facility_id' => null]);
    $staff = User::factory()->create(['facility_id' => $facility->id, 'role' => Role::Staff, 'permissions' => ['lab.view']]);

    $this->actingAs($operator)
        ->patch(route('platform.facilities.admins.update', [$facility, $staff]), ['is_active' => '0'])
        ->assertNotFound();
    $this->actingAs($operator)
        ->patch(route('platform.facilities.admins.update', [$facility, $otherAdmin]), ['is_active' => '0'])
        ->assertNotFound();

    expect($staff->fresh()->is_active)->toBeTrue();
    expect($otherAdmin->fresh()->is_active)->toBeTrue();
});

test('a facility administrator cannot manage other facilities administrators', function () {
    [$facility, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->get(route('platform.facilities.admins.index', $facility))
        ->assertForbidden();
});

test('administrators see users as a list and edit each one on its own page', function () {
    [$facility, $admin] = provisionHospital();
    $nurse = User::factory()->create([
        'facility_id' => $facility->id,
        'name' => 'Grace Nurse',
        'role' => Role::Staff,
        'permissions' => [],
    ]);
    [, $otherAdmin] = provisionHospital();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Grace Nurse')
        ->assertSee(route('admin.users.edit', $nurse), false)
        ->assertDontSee('name="areas[]"', false)
        ->assertDontSee($otherAdmin->email);

    $this->actingAs($admin)->get(route('admin.users.edit', $nurse))->assertOk()->assertSee('name="areas[]"', false);
    $this->actingAs($admin)->get(route('admin.users.edit', $otherAdmin))->assertNotFound();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $nurse), [
            'name' => 'Grace Achan',
            'email' => $nurse->email,
            'phone' => '0772555666',
            'account' => 'staff',
            'areas' => ['clinical'],
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.users.index'));

    $nurse->refresh();
    expect($nurse->name)->toBe('Grace Achan')
        ->and($nurse->phone)->toBe('0772555666')
        ->and($nurse->permits('consultations.manage'))->toBeTrue()
        ->and($nurse->permits('lab.manage'))->toBeFalse();
});

test('an administrator cannot turn off their own account', function () {
    [, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'account' => 'administrator',
            'is_active' => '0',
        ])
        ->assertSessionHas('error');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('the visit page explains empty catalogues instead of showing empty order forms', function () {
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);

    $this->actingAs($admin)
        ->get(route('visits.show', $visit))
        ->assertOk()
        ->assertSee(route('visits.triage', $visit), false)
        ->assertSee(route('visits.consult', $visit), false)
        ->assertSee('No lab tests are on the price list yet')
        ->assertSee('No medicines are on the price list yet')
        ->assertDontSee(route('visits.prescribe', $visit), false);
});

test('the seeded visit page shows every order form and the full record', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::query()->where('email', 'admin@stmarys.tunzuri.test')->first();
    $visit = Visit::query()->whereHas('prescriptions')->first();

    $this->actingAs($admin)
        ->get(route('visits.show', $visit))
        ->assertOk()
        ->assertSee(route('visits.prescribe', $visit), false)
        ->assertSee(route('visits.labs', $visit), false)
        ->assertSee('Prescriptions')
        ->assertSee('dispensed');
});

test('the new registration shortcut shows on front desk pages only', function () {
    [, $admin] = provisionHospital();

    $this->actingAs($admin)->get(route('patients.index'))->assertOk()->assertSee('New registration');
    $this->actingAs($admin)->get(route('pharmacy.index'))->assertOk()->assertDontSee('New registration');
});
