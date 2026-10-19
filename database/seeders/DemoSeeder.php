<?php

namespace Database\Seeders;

use App\Enums\BillingType;
use App\Enums\Payer;
use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\VisitType;
use App\Models\Facility;
use App\Models\ImagingStudy;
use App\Models\InsuranceScheme;
use App\Models\Item;
use App\Models\LabTest;
use App\Models\Service;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Services\EncounterService;
use App\Services\FacilityProvisioner;
use App\Services\PatientRegistrar;
use App\Services\ProcurementService;
use App\Support\FacilityContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $provisioner = app(FacilityProvisioner::class);

        $stMarys = $provisioner->provision([
            'name' => "St. Mary's Community Hospital",
            'code' => 'SMH',
            'kind' => 'faith_based',
            'district' => 'Kampala',
            'address' => 'Nsambya Road',
            'phone' => '0414000001',
            'bed_capacity' => 80,
            'moh_code' => 'UG-KLA-SMH',
        ], [
            'name' => 'Sarah Namakula',
            'email' => 'admin@stmarys.afyadesk.test',
            'password' => 'password',
        ]);

        $this->staff($stMarys);
        $this->catalogue($stMarys);
        $this->story($stMarys);

        $provisioner->provision([
            'name' => 'Hope Clinic',
            'code' => 'HOPE',
            'kind' => 'clinic',
            'district' => 'Wakiso',
            'address' => 'Entebbe Road',
            'bed_capacity' => 24,
        ], [
            'name' => 'Peter Okello',
            'email' => 'admin@hope.afyadesk.test',
            'password' => 'password',
        ]);

        FacilityContext::forget();

        User::query()->create([
            'name' => 'Platform Operator',
            'email' => 'platform@afyadesk.test',
            'password' => 'password',
            'role' => Role::PlatformAdmin,
            'facility_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Auth::logout();
        FacilityContext::forget();
    }

    private function staff(Facility $facility): void
    {
        $people = [
            ['Achieng Reception', 'receptionist@stmarys.afyadesk.test', Role::Receptionist],
            ['Joan Triage', 'triage@stmarys.afyadesk.test', Role::TriageNurse],
            ['Dr. Daniel Kato', 'doctor@stmarys.afyadesk.test', Role::Clinician],
            ['Nurse Betty', 'nurse@stmarys.afyadesk.test', Role::WardNurse],
            ['Moses Lab', 'lab@stmarys.afyadesk.test', Role::LabTechnician],
            ['Rita Images', 'radiology@stmarys.afyadesk.test', Role::Radiographer],
            ['Paul Pharmacy', 'pharmacist@stmarys.afyadesk.test', Role::Pharmacist],
            ['Grace Cashier', 'cashier@stmarys.afyadesk.test', Role::Cashier],
            ['Ivan Claims', 'claims@stmarys.afyadesk.test', Role::ClaimsOfficer],
            ['Sam Stores', 'store@stmarys.afyadesk.test', Role::StoreKeeper],
        ];

        foreach ($people as [$name, $email, $role]) {
            User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'facility_id' => $facility->id,
                'role' => $role,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }

    private function catalogue(Facility $facility): void
    {
        FacilityContext::set($facility->id);

        InsuranceScheme::query()->create([
            'facility_id' => $facility->id,
            'name' => 'Jubilee Health',
            'kind' => 'private',
            'cover_limit' => 2000000,
            'requires_preauthorisation' => true,
            'is_active' => true,
        ]);

        $malariaService = Service::query()->create([
            'facility_id' => $facility->id,
            'code' => 'MRDT',
            'name' => 'Malaria RDT',
            'category' => 'lab',
            'cash_price' => 10000,
            'is_active' => true,
        ]);

        Service::query()->create([
            'facility_id' => $facility->id,
            'code' => 'CXR',
            'name' => 'Chest X-ray',
            'category' => 'imaging',
            'cash_price' => 40000,
            'is_active' => true,
        ]);

        $reagent = Item::query()->create([
            'facility_id' => $facility->id,
            'sku' => 'RDT-KIT',
            'name' => 'Malaria RDT kit',
            'category' => 'reagent',
            'billing_type' => BillingType::NonBillable,
            'unit' => 'test',
            'cash_price' => 0,
            'reorder_level' => 20,
            'is_active' => true,
        ]);

        LabTest::query()->create([
            'facility_id' => $facility->id,
            'service_id' => $malariaService->id,
            'code' => 'MRDT',
            'name' => 'Malaria RDT',
            'specimen' => 'Blood',
            'reference_text' => 'Negative',
            'reagent_item_id' => $reagent->id,
            'reagent_quantity' => 1,
            'is_active' => true,
        ]);

        $studyService = Service::query()->where('code', 'CXR')->first();

        ImagingStudy::query()->create([
            'facility_id' => $facility->id,
            'service_id' => $studyService->id,
            'code' => 'CXR',
            'name' => 'Chest X-ray',
            'is_active' => true,
        ]);

        $paracetamol = Item::query()->create([
            'facility_id' => $facility->id,
            'sku' => 'PARA500',
            'name' => 'Paracetamol 500mg',
            'category' => 'medicine',
            'billing_type' => BillingType::BillableMedicine,
            'unit' => 'tablet',
            'cash_price' => 500,
            'reorder_level' => 100,
            'is_active' => true,
        ]);

        Item::query()->create([
            'facility_id' => $facility->id,
            'sku' => 'GLOVE',
            'name' => 'Examination gloves',
            'category' => 'consumable',
            'billing_type' => BillingType::NonBillable,
            'unit' => 'pair',
            'cash_price' => 0,
            'reorder_level' => 50,
            'is_active' => true,
        ]);

        Item::query()->create([
            'facility_id' => $facility->id,
            'sku' => 'CANN18',
            'name' => 'Cannula 18G',
            'category' => 'consumable',
            'billing_type' => BillingType::BillableConsumable,
            'unit' => 'piece',
            'cash_price' => 2000,
            'reorder_level' => 20,
            'is_active' => true,
        ]);

        $store = User::query()->where('email', 'store@stmarys.afyadesk.test')->first();
        Auth::login($store);

        $supplier = Supplier::query()->create([
            'facility_id' => $facility->id,
            'name' => 'Joint Medical Stores',
            'phone' => '0414250000',
            'is_active' => true,
        ]);

        $procurement = app(ProcurementService::class);
        $order = $procurement->order($supplier, $store, [
            ['item_id' => $paracetamol->id, 'quantity' => 500, 'unit_cost' => 200],
            ['item_id' => $reagent->id, 'quantity' => 100, 'unit_cost' => 3000],
        ]);

        foreach ($order->lines as $line) {
            $procurement->receive($order->fresh(), $line->id, $store, [
                'batch_number' => 'BATCH-'.$line->item_id,
                'expiry_date' => now()->addYear()->toDateString(),
                'quantity' => $line->quantity,
            ]);
        }

        $pharmacy = StockLocation::query()->where('kind', 'pharmacy')->first();
        $labStore = StockLocation::query()->where('kind', 'laboratory')->first();

        $pharmacyRequest = $procurement->requestStock($pharmacy, $store, [
            ['item_id' => $paracetamol->id, 'quantity' => 200],
        ]);
        $procurement->issueRequisition($pharmacyRequest, $store);

        $labRequest = $procurement->requestStock($labStore, $store, [
            ['item_id' => $reagent->id, 'quantity' => 40],
        ]);
        $procurement->issueRequisition($labRequest, $store);
    }

    private function story(Facility $facility): void
    {
        FacilityContext::set($facility->id);
        $registrar = app(PatientRegistrar::class);
        $encounters = app(EncounterService::class);
        $receptionist = User::query()->where('email', 'receptionist@stmarys.afyadesk.test')->first();
        $nurse = User::query()->where('email', 'triage@stmarys.afyadesk.test')->first();
        $doctor = User::query()->where('email', 'doctor@stmarys.afyadesk.test')->first();

        Auth::login($receptionist);

        $amina = $registrar->register($facility, $receptionist, $this->person('Nakato', 'Amina', 'female', '1994-04-12', '0772000001', 'Kampala'));
        $john = $registrar->register($facility, $receptionist, $this->person('Okello', 'John', 'male', '1988-11-02', '0703000002', 'Wakiso'));

        $department = $facility->departments()->where('code', 'OPD')->first();

        $aminaVisit = $encounters->openVisit($amina, $receptionist, [
            'visit_type' => VisitType::Outpatient,
            'priority' => Priority::Urgent,
            'payer' => Payer::Cash,
            'department_id' => $department->id,
            'chief_complaint' => 'Fever and headache',
        ]);

        Auth::login($nurse);
        $encounters->recordTriage($aminaVisit, $nurse, [
            'temperature' => 38.6,
            'pulse' => 104,
            'respiration' => 22,
            'systolic' => 110,
            'diastolic' => 70,
            'spo2' => 98,
            'priority' => Priority::Urgent,
            'chief_complaint' => 'Fever and headache',
        ]);

        Auth::login($receptionist);
        $johnVisit = $encounters->openVisit($john, $receptionist, [
            'visit_type' => VisitType::Outpatient,
            'priority' => Priority::Routine,
            'payer' => Payer::Cash,
            'department_id' => $department->id,
            'chief_complaint' => 'Cough',
        ]);

        Auth::login($doctor);
        $encounters->recordConsultation($johnVisit, $doctor, [
            'history' => 'Cough for three days.',
            'examination' => 'Chest clear.',
            'plan' => 'Malaria test and paracetamol.',
            'icd10_codes' => ['J06.9'],
        ]);

        $test = LabTest::query()->where('code', 'MRDT')->first();
        $encounters->orderLabs($johnVisit, $doctor, [$test->id], 'Rule out malaria');

        $medicine = Item::query()->where('sku', 'PARA500')->first();
        $encounters->prescribe($johnVisit, $doctor, [[
            'item_id' => $medicine->id,
            'dose' => '1g',
            'frequency' => 'TDS',
            'duration_days' => 3,
            'quantity' => 9,
        ]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function person(string $surname, string $given, string $sex, string $dob, string $phone, string $district): array
    {
        return [
            'surname' => $surname,
            'given_name' => $given,
            'sex' => $sex,
            'date_of_birth' => $dob,
            'phone' => $phone,
            'district' => $district,
            'next_of_kin_name' => $given.' kin',
            'next_of_kin_relationship' => 'Sibling',
            'next_of_kin_phone' => '0700000000',
            'confirm_duplicate' => false,
        ];
    }
}
