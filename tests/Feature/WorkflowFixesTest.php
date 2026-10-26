<?php

use App\Authorization\RolePermissions;
use App\Enums\Role;
use App\Models\Admission;
use App\Models\AntenatalVisit;
use App\Models\Appointment;
use App\Models\Bed;
use App\Models\Bill;
use App\Models\Facility;
use App\Models\ImagingOrder;
use App\Models\ImagingStudy;
use App\Models\InsuranceScheme;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Preauthorisation;
use App\Models\QueueEntry;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use App\Models\Waiver;
use App\Services\CashierService;
use App\Services\EncounterService;
use App\Support\FacilityContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function staffFor(Facility $facility, string $area): User
{
    return User::factory()->create([
        'facility_id' => $facility->id,
        'role' => Role::Staff,
        'permissions' => RolePermissions::permissionsForAreas([$area]),
    ]);
}

function admitPatient(Facility $facility, User $admin): Admission
{
    $visit = openCashVisit($facility, $admin);
    FacilityContext::set($facility->id);
    $admission = app(EncounterService::class)->admit($visit, $admin, Bed::query()->first(), 'Malaria');
    FacilityContext::forget();

    return $admission;
}

test('a discharged patient cannot be charted, issued stock, or discharged again', function () {
    [$facility, $admin] = provisionHospital();
    $admission = admitPatient($facility, $admin);
    $admission->update(['status' => 'discharged', 'discharged_at' => now()]);

    $this->actingAs($admin)
        ->post(route('wards.notes', $admission), ['note' => 'Late entry'])
        ->assertSessionHasErrors('admission');
    $this->actingAs($admin)
        ->post(route('wards.discharge', $admission), ['discharge_summary' => 'Again', 'discharge_outcome' => 'recovered'])
        ->assertSessionHasErrors('admission');

    expect($admission->nursingNotes()->count())->toBe(0);

    $this->actingAs($admin)
        ->get(route('wards.show', $admission))
        ->assertOk()
        ->assertSee('The ward record is closed')
        ->assertDontSee(route('wards.notes', $admission), false);
});

test('a preauthorisation cannot be decided twice', function () {
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);
    $scheme = InsuranceScheme::query()->create(['facility_id' => $facility->id, 'name' => 'Jubilee', 'kind' => 'private', 'is_active' => true]);
    $preauth = Preauthorisation::query()->create([
        'facility_id' => $facility->id,
        'visit_id' => $visit->id,
        'insurance_scheme_id' => $scheme->id,
        'description' => 'Ultrasound',
        'amount' => 50000,
        'status' => 'approved',
    ]);

    $this->actingAs($admin)
        ->post(route('insurance.preauthorisations.decide', $preauth), ['decision' => 'rejected'])
        ->assertSessionHas('error');

    expect($preauth->fresh()->status)->toBe('approved');
});

test('checking in an insured patient opens an insured visit', function () {
    [$facility, $admin] = provisionHospital();
    $patient = openCashVisit($facility, $admin)->patient;
    $scheme = InsuranceScheme::query()->create(['facility_id' => $facility->id, 'name' => 'Jubilee', 'kind' => 'private', 'is_active' => true]);
    $appointment = Appointment::query()->create([
        'facility_id' => $facility->id,
        'patient_id' => $patient->id,
        'department_id' => $facility->departments()->first()->id,
        'scheduled_at' => now()->addHour(),
        'status' => 'booked',
    ]);

    $this->actingAs($admin)->post(route('appointments.check-in', $appointment), [
        'payer' => 'insurance',
        'insurance_scheme_id' => $scheme->id,
        'member_number' => 'JUB-1',
    ])->assertRedirect();

    $visit = Visit::query()->latest('id')->first();
    expect($visit->payer->value)->toBe('insurance')
        ->and($visit->insurance_scheme_id)->toBe($scheme->id)
        ->and($appointment->fresh()->status)->toBe('checked_in');
});

test('appointments list today and upcoming first and can be cancelled', function () {
    [$facility, $admin] = provisionHospital();
    $patient = openCashVisit($facility, $admin)->patient;
    $department = $facility->departments()->first();
    $past = Appointment::query()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'department_id' => $department->id, 'scheduled_at' => now()->subWeek(), 'status' => 'booked', 'reason' => 'Old review']);
    $upcoming = Appointment::query()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'department_id' => $department->id, 'scheduled_at' => now()->addDay(), 'status' => 'booked', 'reason' => 'Next review']);

    $this->actingAs($admin)->get(route('appointments.index'))->assertSee('Next review')->assertDontSee('Old review');
    $this->actingAs($admin)->get(route('appointments.index', ['when' => 'past']))->assertSee('Old review');

    $this->actingAs($admin)->post(route('appointments.status', $upcoming), ['status' => 'cancelled'])->assertRedirect();
    expect($upcoming->fresh()->status)->toBe('cancelled');
    expect($past->fresh()->status)->toBe('booked');
});

test('maternity finds the mother by patient number and refuses male patients', function () {
    [$facility, $admin] = provisionHospital();
    $man = openCashVisit($facility, $admin)->patient;
    $woman = Patient::query()->create([...$man->only(['facility_id', 'district', 'next_of_kin_name', 'next_of_kin_relationship', 'next_of_kin_phone', 'date_of_birth']), 'patient_number' => 'TST-MUM', 'surname' => 'Auma', 'given_name' => 'Grace', 'sex' => 'female', 'phone' => '0700111222', 'consent_at' => now()]);

    $this->actingAs($admin)
        ->post(route('maternity.antenatal'), ['patient_number' => $man->patient_number, 'gestation_weeks' => 20])
        ->assertSessionHasErrors('patient_number');
    $this->actingAs($admin)
        ->post(route('maternity.antenatal'), ['patient_number' => 'TST-MUM', 'gestation_weeks' => 20])
        ->assertSessionHasNoErrors();

    expect(AntenatalVisit::query()->where('patient_id', $woman->id)->count())->toBe(1)
        ->and(AntenatalVisit::query()->where('patient_id', $man->id)->exists())->toBeFalse();
});

test('call next only shows for queues the person can serve, and served patients can be marked done', function () {
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);
    $reception = staffFor($facility, 'reception');

    $this->actingAs($reception)->get(route('queues.index', ['stage' => 'laboratory']))->assertDontSee(route('queues.call'), false);
    $this->actingAs($reception)->get(route('queues.index', ['stage' => 'cashier']))->assertSee(route('queues.call'), false);

    $entry = QueueEntry::query()->where('visit_id', $visit->id)->where('stage', 'triage')->first();
    $entry->update(['status' => 'serving']);

    $this->actingAs($reception)->post(route('queues.complete', $entry))->assertForbidden();
    $this->actingAs($admin)->post(route('queues.complete', $entry))->assertRedirect();
    expect($entry->fresh()->status)->toBe('completed');
});

test('a visit that was never triaged can still be completed once paid', function () {
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);
    $bill = Bill::query()->where('visit_id', $visit->id)->first();

    $this->actingAs($admin)->post(route('cashier.shift'), ['opening_float' => 0]);
    $this->actingAs($admin)->post(route('cashier.pay', $bill), ['method' => 'cash', 'amount' => 5000]);
    $this->actingAs($admin)->post(route('visits.complete', $visit))->assertSessionHasNoErrors();

    expect($visit->fresh()->status->value)->toBe('closed');

    $this->actingAs($admin)
        ->get(route('visits.show', $visit))
        ->assertSee('cannot take new orders')
        ->assertDontSee(route('visits.triage', $visit), false)
        ->assertDontSee(route('visits.complete', $visit), false);
});

test('a radiology report keeps its image viewable and cannot be overwritten', function () {
    Storage::fake('local');
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);
    $service = Service::query()->create(['facility_id' => $facility->id, 'code' => 'XR', 'name' => 'X-ray', 'category' => 'imaging', 'cash_price' => 20000, 'is_active' => true]);
    $study = ImagingStudy::query()->create(['facility_id' => $facility->id, 'service_id' => $service->id, 'code' => 'CXR', 'name' => 'Chest X-ray', 'is_active' => true]);
    FacilityContext::set($facility->id);
    $order = app(EncounterService::class)->orderImaging($visit, $admin, $study);
    FacilityContext::forget();

    $this->actingAs($admin)->post(route('radiology.report', $order), [
        'report' => 'Clear lungs.',
        'image' => UploadedFile::fake()->image('chest.jpg'),
    ])->assertSessionHasNoErrors();

    $this->actingAs($admin)->get(route('radiology.image', $order))->assertOk();
    $this->actingAs($admin)->get(route('radiology.index'))->assertSee(route('radiology.image', $order), false);

    $this->actingAs($admin)
        ->post(route('radiology.report', $order), ['report' => 'Changed.'])
        ->assertSessionHasErrors('report');
    expect(ImagingOrder::query()->find($order->id)->report)->toBe('Clear lungs.');
});

test('a malformed report date is a validation error, not a crash', function () {
    [, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->get(route('reports.index', ['start' => 'not-a-date']))
        ->assertRedirect()
        ->assertSessionHasErrors('start');
});

test('an approver can reject a waiver and the cashier sees the decision', function () {
    [$facility, $admin] = provisionHospital();
    $visit = openCashVisit($facility, $admin);
    $bill = Bill::query()->where('visit_id', $visit->id)->first();
    $cashier = staffFor($facility, 'reception');
    $waiver = app(CashierService::class)->requestWaiver($bill, $cashier, 2000, 'Hardship');

    $this->actingAs($cashier)->post(route('waivers.reject', $waiver))->assertForbidden();
    $this->actingAs($admin)->post(route('waivers.reject', $waiver))->assertRedirect();

    expect(Waiver::query()->find($waiver->id)->status)->toBe('rejected');
    $this->actingAs($cashier)->get(route('cashier.index'))->assertSee('Rejected')->assertSee('decided by '.$admin->name);
});

test('lab staff see results but not the day takings, and tabs are titled by page', function () {
    [$facility] = provisionHospital();
    $lab = staffFor($facility, 'laboratory');

    $this->actingAs($lab)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<title>Dashboard · ', false)
        ->assertDontSee('Collected today')
        ->assertSee('Laboratory');
});

test('the laboratory page puts emergencies first, groups a request, and keeps a searchable results log', function () {
    [$facility, $admin] = provisionHospital();
    $service = Service::query()->create(['facility_id' => $facility->id, 'code' => 'LAB', 'name' => 'Lab', 'category' => 'laboratory', 'cash_price' => 3000, 'is_active' => true]);
    $malaria = LabTest::query()->create(['facility_id' => $facility->id, 'service_id' => $service->id, 'code' => 'BS', 'name' => 'Malaria smear', 'is_active' => true]);
    $hb = LabTest::query()->create(['facility_id' => $facility->id, 'service_id' => $service->id, 'code' => 'HB', 'name' => 'Haemoglobin', 'unit' => 'g/dL', 'is_active' => true]);

    $routine = openCashVisit($facility, $admin);
    $emergency = openCashVisit($facility, $admin);
    $emergency->update(['priority' => 'emergency']);
    $emergency->patient->update(['surname' => 'Emergencyperson']);
    FacilityContext::set($facility->id);
    app(EncounterService::class)->orderLabs($routine, $admin, [$malaria->id]);
    $request = app(EncounterService::class)->orderLabs($emergency, $admin, [$malaria->id, $hb->id]);
    FacilityContext::forget();

    $page = $this->actingAs($admin)->get(route('lab.index'))->assertOk();
    $html = (string) $page->getContent();
    expect(strpos($html, 'Emergencyperson'))->toBeLessThan(strpos($html, $routine->patient->fullName()))
        ->and(substr_count($html, 'Emergencyperson'))->toBe(1);
    $page->assertSee('g/dL');

    $hbItem = $request->items()->where('lab_test_id', $hb->id)->first();
    $this->actingAs($admin)->post(route('lab.verify', $hbItem), ['result_value' => '6.1', 'result_flag' => 'critical'])->assertSessionHasNoErrors();

    $this->actingAs($admin)->get(route('lab.index', ['tab' => 'verified', 'q' => 'Haemo']))
        ->assertSee('6.1 g/dL')
        ->assertSee('Critical');
    $this->actingAs($admin)->get(route('lab.index'))->assertSee('1 flagged');

    $clinician = staffFor($facility, 'clinical');
    $this->actingAs($clinician)->get(route('lab.index'))->assertOk()->assertDontSee('name="result_value"', false);
});
