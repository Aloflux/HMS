<?php

namespace Database\Seeders;

use App\Authorization\RolePermissions;
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

        $this->catalogue($stMarys);
        $this->stations($stMarys);
        $this->story($stMarys);

        $provisioner->provision([
            'name' => 'Hope Clinic',
            'code' => 'HOPE',
            'kind' => 'clinic',
            'district' => 'Wakiso',
            'address' => 'Entebbe Road',
            'phone' => '0414000002',
            'bed_capacity' => 12,
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

        $items = [];

        foreach ($this->itemRows() as $row) {
            $items[$row['sku']] = Item::query()->create([
                'facility_id' => $facility->id,
                'sku' => $row['sku'],
                'name' => $row['name'],
                'category' => $row['category'],
                'billing_type' => $row['billing'],
                'unit' => $row['unit'],
                'cash_price' => $row['price'],
                'reorder_level' => $row['reorder'],
                'is_active' => true,
            ]);
        }

        $this->diagnostics($facility, $items);

        $keeper = $this->administrator($facility);
        Auth::login($keeper);

        $jms = Supplier::query()->create([
            'facility_id' => $facility->id,
            'name' => 'Joint Medical Stores',
            'phone' => '0414250000',
            'is_active' => true,
        ]);

        $nms = Supplier::query()->create([
            'facility_id' => $facility->id,
            'name' => 'National Medical Stores',
            'phone' => '0414320000',
            'is_active' => true,
        ]);

        $procurement = app(ProcurementService::class);
        $rows = $this->itemRows();

        $this->purchase($procurement, $jms, $keeper, $items, array_values(array_filter(
            $rows,
            fn (array $row): bool => $row['supplier'] === 'jms',
        )));

        $this->purchase($procurement, $nms, $keeper, $items, array_values(array_filter(
            $rows,
            fn (array $row): bool => $row['supplier'] === 'nms',
        )));

        $issues = [];

        foreach ($rows as $row) {
            foreach ($row['issues'] as $locationName => $quantity) {
                $issues[$locationName][$row['sku']] = $quantity;
            }
        }

        foreach (['Pharmacy', 'Laboratory', 'Female ward store', 'Male ward store', 'Theatre store'] as $locationName) {
            $location = StockLocation::query()->where('name', $locationName)->firstOrFail();
            $lines = [];

            foreach ($issues[$locationName] as $sku => $quantity) {
                $lines[] = ['item_id' => $items[$sku]->id, 'quantity' => $quantity];
            }

            $requisition = $procurement->requestStock($location, $keeper, $lines, 'Working stock for '.$locationName);
            $procurement->issueRequisition($requisition, $keeper);
        }
    }

    /**
     * @param  array<string, Item>  $items
     */
    private function diagnostics(Facility $facility, array $items): void
    {
        foreach ([
            ['MRDT', 'Malaria RDT', 'Blood', 'Negative', 10000, 'RDT-KIT', 1],
            ['GLU', 'Blood glucose', 'Blood', '3.5–7.8 mmol/L', 8000, 'GLUSTR', 1],
            ['UDIP', 'Urinalysis', 'Urine', 'No protein, no glucose', 7000, 'UDIP', 1],
            ['CBC', 'Complete blood count', 'Blood', 'See analyser printout', 15000, 'EDTA', 1],
        ] as [$code, $name, $specimen, $reference, $price, $sku, $quantity]) {
            $service = Service::query()->create([
                'facility_id' => $facility->id,
                'code' => $code,
                'name' => $name,
                'category' => 'lab',
                'cash_price' => $price,
                'is_active' => true,
            ]);

            LabTest::query()->create([
                'facility_id' => $facility->id,
                'service_id' => $service->id,
                'code' => $code,
                'name' => $name,
                'specimen' => $specimen,
                'reference_text' => $reference,
                'reagent_item_id' => $items[$sku]->id,
                'reagent_quantity' => $quantity,
                'is_active' => true,
            ]);
        }

        foreach ([
            ['CXR', 'Chest X-ray', 40000],
            ['USABD', 'Abdominal ultrasound', 35000],
        ] as [$code, $name, $price]) {
            $service = Service::query()->create([
                'facility_id' => $facility->id,
                'code' => $code,
                'name' => $name,
                'category' => 'imaging',
                'cash_price' => $price,
                'is_active' => true,
            ]);

            ImagingStudy::query()->create([
                'facility_id' => $facility->id,
                'service_id' => $service->id,
                'code' => $code,
                'name' => $name,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @param  array<string, Item>  $items
     * @param  list<array<string, mixed>>  $rows
     */
    private function purchase(ProcurementService $procurement, Supplier $supplier, User $actor, array $items, array $rows): void
    {
        $order = $procurement->order($supplier, $actor, array_map(
            fn (array $row): array => [
                'item_id' => $items[$row['sku']]->id,
                'quantity' => array_sum(array_column($row['batches'], 2)),
                'unit_cost' => $row['cost'],
            ],
            $rows,
        ));

        $lines = $order->lines->keyBy('item_id');

        foreach ($rows as $row) {
            $line = $lines[$items[$row['sku']]->id];

            foreach ($row['batches'] as [$number, $months, $quantity]) {
                $procurement->receive($order->fresh(), $line->id, $actor, [
                    'batch_number' => $number,
                    'expiry_date' => now()->addMonths($months)->toDateString(),
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    /**
     * @return list<array{sku: string, name: string, category: string, billing: BillingType, unit: string, price: int, reorder: int, supplier: string, cost: int, batches: list<array{0: string, 1: int, 2: int}>, issues: array<string, int>}>
     */
    private function itemRows(): array
    {
        $medicine = BillingType::BillableMedicine;
        $consumable = BillingType::BillableConsumable;
        $stock = BillingType::NonBillable;

        return [
            $this->row('PARA500', 'Paracetamol 500mg', 'medicine', $medicine, 'tablet', 500, 400, 'jms', 200, [['JMS-P24-114', 18, 2000], ['JMS-P25-038', 8, 1200]], ['Pharmacy' => 1500]),
            $this->row('AMOX500', 'Amoxicillin 500mg', 'medicine', $medicine, 'capsule', 800, 200, 'jms', 350, [['JMS-AX24-220', 16, 1100], ['JMS-AX25-041', 6, 700]], ['Pharmacy' => 900]),
            $this->row('AL20', 'Artemether-lumefantrine 20/120mg', 'medicine', $medicine, 'tablet', 1500, 150, 'jms', 800, [['JMS-AL24-018', 14, 700], ['JMS-AL25-090', 5, 400]], ['Pharmacy' => 550]),
            $this->row('ORS', 'Oral rehydration salts', 'medicine', $medicine, 'sachet', 500, 100, 'jms', 200, [['JMS-ORS24-300', 12, 1400]], ['Pharmacy' => 500]),
            $this->row('MET400', 'Metronidazole 400mg', 'medicine', $medicine, 'tablet', 400, 120, 'jms', 180, [['JMS-MT24-155', 20, 1200]], ['Pharmacy' => 400]),
            $this->row('IBU400', 'Ibuprofen 400mg', 'medicine', $medicine, 'tablet', 600, 80, 'jms', 250, [['JMS-IB24-077', 18, 950]], ['Pharmacy' => 300]),
            $this->row('CIP500', 'Ciprofloxacin 500mg', 'medicine', $medicine, 'tablet', 1000, 60, 'jms', 400, [['JMS-CP24-012', 22, 700]], ['Pharmacy' => 200]),
            $this->row('ALB400', 'Albendazole 400mg', 'medicine', $medicine, 'tablet', 1000, 40, 'jms', 300, [['JMS-AB24-066', 24, 400]], ['Pharmacy' => 120]),
            $this->row('COT480', 'Cotrimoxazole 480mg', 'medicine', $medicine, 'tablet', 300, 100, 'jms', 150, [['JMS-CT24-201', 18, 1100]], ['Pharmacy' => 400]),
            $this->row('DIC50', 'Diclofenac 50mg', 'medicine', $medicine, 'tablet', 400, 50, 'jms', 200, [['JMS-DC24-088', 16, 550]], ['Pharmacy' => 180]),
            $this->row('OXY10', 'Oxytocin 10 IU', 'medicine', $medicine, 'ampoule', 2500, 20, 'jms', 800, [['JMS-OX24-009', 18, 120]], ['Pharmacy' => 40, 'Theatre store' => 24]),
            $this->row('GENT80', 'Gentamicin 80mg/2ml', 'medicine', $medicine, 'ampoule', 1500, 20, 'jms', 600, [['JMS-GN24-044', 18, 180]], ['Pharmacy' => 50, 'Female ward store' => 12, 'Male ward store' => 12]),
            $this->row('NS500', 'Sodium chloride 0.9% 500ml', 'medicine', $medicine, 'bottle', 5000, 40, 'nms', 2500, [['NMS-NS26-014', 24, 180], ['NMS-NS26-090', 10, 120]], ['Pharmacy' => 150, 'Female ward store' => 24, 'Male ward store' => 24]),
            $this->row('D5W', 'Dextrose 5% 500ml', 'medicine', $medicine, 'bottle', 4500, 24, 'nms', 2200, [['NMS-DX26-033', 20, 180]], ['Pharmacy' => 40, 'Female ward store' => 16, 'Male ward store' => 16]),
            $this->row('GLOVE', 'Examination gloves', 'consumable', $stock, 'pair', 0, 80, 'nms', 400, [['NMS-GL26-500', 36, 220]], ['Female ward store' => 40, 'Male ward store' => 40, 'Theatre store' => 30]),
            $this->row('SGLOVE', 'Surgical gloves', 'consumable', $stock, 'pair', 0, 20, 'nms', 800, [['NMS-SG26-014', 36, 90]], ['Theatre store' => 30]),
            $this->row('CANN18', 'Cannula 18G', 'consumable', $consumable, 'piece', 2000, 30, 'nms', 800, [['NMS-CN26-018', 36, 300]], ['Female ward store' => 40, 'Male ward store' => 36, 'Theatre store' => 16]),
            $this->row('CANN22', 'Cannula 22G', 'consumable', $consumable, 'piece', 2000, 20, 'nms', 800, [['NMS-CN26-022', 36, 180]], ['Female ward store' => 24, 'Male ward store' => 20]),
            $this->row('SYR5', 'Syringe 5ml', 'consumable', $consumable, 'piece', 300, 80, 'nms', 150, [['NMS-SY26-005', 48, 900]], ['Pharmacy' => 80, 'Female ward store' => 60, 'Male ward store' => 60, 'Theatre store' => 40]),
            $this->row('SYR10', 'Syringe 10ml', 'consumable', $consumable, 'piece', 400, 40, 'nms', 200, [['NMS-SY26-010', 48, 400]], ['Pharmacy' => 30, 'Female ward store' => 40, 'Male ward store' => 40, 'Theatre store' => 30]),
            $this->row('COTTON', 'Cotton wool', 'consumable', $stock, 'roll', 0, 12, 'nms', 1500, [['NMS-CW26-001', 24, 90]], ['Female ward store' => 12, 'Male ward store' => 12, 'Theatre store' => 8]),
            $this->row('GAUZE', 'Gauze swabs', 'consumable', $stock, 'packet', 0, 20, 'nms', 800, [['NMS-GZ26-010', 36, 140]], ['Female ward store' => 16, 'Male ward store' => 16, 'Theatre store' => 30]),
            $this->row('SPIRIT', 'Surgical spirit 100ml', 'consumable', $stock, 'bottle', 0, 12, 'nms', 3000, [['NMS-SP26-100', 24, 28]], ['Theatre store' => 8, 'Female ward store' => 6, 'Male ward store' => 6]),
            $this->row('SUTURE', 'Vicryl 2-0 suture', 'consumable', $consumable, 'piece', 8000, 10, 'nms', 3500, [['NMS-SU26-020', 36, 70]], ['Theatre store' => 20]),
            $this->row('RDT-KIT', 'Malaria RDT kit', 'reagent', $stock, 'test', 0, 40, 'nms', 3000, [['NMS-RD26-100', 12, 300]], ['Laboratory' => 100]),
            $this->row('GLUSTR', 'Glucose test strips', 'reagent', $stock, 'strip', 0, 25, 'nms', 500, [['NMS-GS26-050', 14, 180]], ['Laboratory' => 60]),
            $this->row('UDIP', 'Urine dipsticks', 'reagent', $stock, 'strip', 0, 20, 'nms', 400, [['NMS-UD26-010', 18, 150]], ['Laboratory' => 50]),
            $this->row('EDTA', 'EDTA blood tube', 'reagent', $stock, 'piece', 0, 30, 'nms', 200, [['NMS-ED26-004', 24, 200]], ['Laboratory' => 50]),
        ];
    }

    /**
     * @param  list<array{0: string, 1: int, 2: int}>  $batches
     * @param  array<string, int>  $issues
     * @return array{sku: string, name: string, category: string, billing: BillingType, unit: string, price: int, reorder: int, supplier: string, cost: int, batches: list<array{0: string, 1: int, 2: int}>, issues: array<string, int>}
     */
    private function row(
        string $sku,
        string $name,
        string $category,
        BillingType $billing,
        string $unit,
        int $price,
        int $reorder,
        string $supplier,
        int $cost,
        array $batches,
        array $issues,
    ): array {
        return [
            'sku' => $sku,
            'name' => $name,
            'category' => $category,
            'billing' => $billing,
            'unit' => $unit,
            'price' => $price,
            'reorder' => $reorder,
            'supplier' => $supplier,
            'cost' => $cost,
            'batches' => $batches,
            'issues' => $issues,
        ];
    }

    private function story(Facility $facility): void
    {
        FacilityContext::set($facility->id);
        $registrar = app(PatientRegistrar::class);
        $encounters = app(EncounterService::class);
        $clerk = User::query()->where('email', 'front@stmarys.afyadesk.test')->firstOrFail();
        $clinician = User::query()->where('email', 'doctor@stmarys.afyadesk.test')->firstOrFail();

        Auth::login($clerk);

        $amina = $registrar->register($facility, $clerk, $this->person('Nakato', 'Amina', 'female', '1994-04-12', '0772000001', 'Kampala'));
        $john = $registrar->register($facility, $clerk, $this->person('Okello', 'John', 'male', '1988-11-02', '0703000002', 'Wakiso'));

        $department = $facility->departments()->where('code', 'OPD')->first();

        $aminaVisit = $encounters->openVisit($amina, $clerk, [
            'visit_type' => VisitType::Outpatient,
            'priority' => Priority::Urgent,
            'payer' => Payer::Cash,
            'department_id' => $department->id,
            'chief_complaint' => 'Fever and headache',
        ]);

        Auth::login($clinician);
        $encounters->recordTriage($aminaVisit, $clinician, [
            'temperature' => 38.6,
            'pulse' => 104,
            'respiration' => 22,
            'systolic' => 110,
            'diastolic' => 70,
            'spo2' => 98,
            'priority' => Priority::Urgent,
            'chief_complaint' => 'Fever and headache',
        ]);

        Auth::login($clerk);
        $johnVisit = $encounters->openVisit($john, $clerk, [
            'visit_type' => VisitType::Outpatient,
            'priority' => Priority::Routine,
            'payer' => Payer::Cash,
            'department_id' => $department->id,
            'chief_complaint' => 'Cough',
        ]);

        Auth::login($clinician);
        $encounters->recordTriage($johnVisit, $clinician, [
            'temperature' => 36.8,
            'pulse' => 82,
            'respiration' => 18,
            'systolic' => 118,
            'diastolic' => 76,
            'spo2' => 99,
            'priority' => Priority::Routine,
            'chief_complaint' => 'Cough',
        ]);

        $encounters->recordConsultation($johnVisit, $clinician, [
            'history' => 'Cough for three days.',
            'examination' => 'Chest clear.',
            'plan' => 'Malaria test and paracetamol.',
            'icd10_codes' => ['J06.9'],
        ]);

        $test = LabTest::query()->where('code', 'MRDT')->firstOrFail();
        $encounters->orderLabs($johnVisit, $clinician, [$test->id], 'Rule out malaria');

        $medicine = Item::query()->where('sku', 'PARA500')->firstOrFail();
        $encounters->prescribe($johnVisit, $clinician, [[
            'item_id' => $medicine->id,
            'dose' => '1g',
            'frequency' => 'TDS',
            'duration_days' => 3,
            'quantity' => 9,
        ]]);

        $encounters->recordConsultation($aminaVisit, $clinician, [
            'history' => 'Fever and headache for two days.',
            'examination' => 'Febrile, chest clear, no neck stiffness.',
            'plan' => 'Malaria test, amoxicillin, and oral rehydration.',
            'icd10_codes' => ['R50.9'],
        ]);

        $encounters->orderLabs($aminaVisit, $clinician, [$test->id], 'Fever');

        $amoxicillin = Item::query()->where('sku', 'AMOX500')->firstOrFail();
        $ors = Item::query()->where('sku', 'ORS')->firstOrFail();
        $encounters->prescribe($aminaVisit, $clinician, [
            [
                'item_id' => $amoxicillin->id,
                'dose' => '500mg',
                'frequency' => 'TDS',
                'duration_days' => 5,
                'quantity' => 15,
            ],
            [
                'item_id' => $ors->id,
                'dose' => '1 sachet',
                'frequency' => 'After loose stool',
                'duration_days' => 2,
                'quantity' => 6,
            ],
        ]);
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

    private function stations(Facility $facility): void
    {
        FacilityContext::set($facility->id);

        $this->staff($facility, 'Achieng Auma', 'front@stmarys.afyadesk.test', ['reception'], 'CASH');
        $this->staff($facility, 'Dr. Daniel Kato', 'doctor@stmarys.afyadesk.test', ['clinical'], 'OPD');
        $this->staff($facility, 'Moses Okello', 'diagnostics@stmarys.afyadesk.test', ['laboratory', 'imaging'], 'LAB');
        $this->staff($facility, 'Paul Mugisha', 'pharmacy@stmarys.afyadesk.test', ['pharmacy', 'stores'], 'PHARM');
    }

    /**
     * @param  list<string>  $areas
     */
    private function staff(Facility $facility, string $name, string $email, array $areas, string $departmentCode): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'facility_id' => $facility->id,
            'role' => Role::Staff,
            'permissions' => RolePermissions::permissionsForAreas($areas),
            'department_id' => $facility->departments()->where('code', $departmentCode)->value('id'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function administrator(Facility $facility): User
    {
        return User::query()
            ->where('facility_id', $facility->id)
            ->where('role', Role::Administrator)
            ->firstOrFail();
    }
}
