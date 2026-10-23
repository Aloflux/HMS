<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\QueueStage;
use App\Enums\StockReason;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Consultation;
use App\Models\Icd10Code;
use App\Models\ImagingOrder;
use App\Models\ImagingStudy;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Service;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EncounterService
{
    public function __construct(
        private SequenceGenerator $sequences,
        private BillingService $billing,
        private PriceBook $prices,
        private QueueBoard $queues,
        private StockLedger $stock,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function openVisit(Patient $patient, User $actor, array $data): Visit
    {
        return DB::transaction(function () use ($patient, $actor, $data): Visit {
            $facility = $patient->facility;

            $visit = Visit::query()->create([
                'facility_id' => $facility->id,
                'patient_id' => $patient->id,
                'visit_number' => $this->sequences->next($facility, 'visit'),
                'visit_type' => $data['visit_type'],
                'status' => VisitStatus::Registered,
                'priority' => $data['priority'] ?? Priority::Routine,
                'department_id' => $data['department_id'] ?? null,
                'payer' => $data['payer'],
                'insurance_scheme_id' => $data['insurance_scheme_id'] ?? null,
                'member_number' => $data['member_number'] ?? null,
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'opened_at' => now(),
                'opened_by' => $actor->id,
            ]);

            $this->billing->openFor($visit);
            $this->chargeService($visit, 'REG', 'registration', $visit->id, 'Registration', true);
            $this->queues->enqueue($visit, QueueStage::Triage);

            return $visit;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordTriage(Visit $visit, User $actor, array $data): Visit
    {
        return DB::transaction(function () use ($visit, $actor, $data): Visit {
            $this->assertAcceptsOrders($visit);

            $visit->triageRecords()->create([
                'facility_id' => $visit->facility_id,
                'temperature' => $data['temperature'] ?? null,
                'pulse' => $data['pulse'] ?? null,
                'respiration' => $data['respiration'] ?? null,
                'systolic' => $data['systolic'] ?? null,
                'diastolic' => $data['diastolic'] ?? null,
                'spo2' => $data['spo2'] ?? null,
                'weight_kg' => $data['weight_kg'] ?? null,
                'height_cm' => $data['height_cm'] ?? null,
                'priority' => $data['priority'],
                'chief_complaint' => $data['chief_complaint'],
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor->id,
            ]);

            $visit->update([
                'priority' => $data['priority'],
                'chief_complaint' => $data['chief_complaint'],
            ]);

            if ($visit->status === VisitStatus::Registered) {
                $visit->moveTo(VisitStatus::Triaged);
            }

            $this->queues->complete($visit, QueueStage::Triage);
            $this->queues->enqueue($visit->fresh(), QueueStage::Consultation);

            return $visit->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordConsultation(Visit $visit, User $actor, array $data): Consultation
    {
        return DB::transaction(function () use ($visit, $actor, $data): Consultation {
            $this->assertAcceptsOrders($visit);

            $consultation = $visit->consultations()->create([
                'facility_id' => $visit->facility_id,
                'clinician_id' => $actor->id,
                'history' => $data['history'] ?? null,
                'examination' => $data['examination'] ?? null,
                'plan' => $data['plan'] ?? null,
                'referral' => $data['referral'] ?? null,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            foreach (array_values($data['icd10_codes']) as $index => $code) {
                $icd = Icd10Code::query()->where('code', $code)->firstOrFail();

                $consultation->diagnoses()->create([
                    'facility_id' => $visit->facility_id,
                    'visit_id' => $visit->id,
                    'icd10_code' => $icd->code,
                    'title' => $icd->title,
                    'rank' => $index === 0 ? 'primary' : 'secondary',
                ]);
            }

            $this->chargeService($visit, 'CONSULT', 'consultation', $consultation->id, 'Consultation');

            $visit->update(['clinician_id' => $actor->id]);

            if (in_array($visit->status, [VisitStatus::Registered, VisitStatus::Triaged], true)) {
                $visit->moveTo(VisitStatus::Consulting);
            }

            $this->queues->complete($visit, QueueStage::Consultation);

            return $consultation;
        });
    }

    /**
     * @param  list<int>  $testIds
     */
    public function orderLabs(Visit $visit, User $actor, array $testIds, ?string $notes = null): LabRequest
    {
        return DB::transaction(function () use ($visit, $actor, $testIds, $notes): LabRequest {
            $this->assertAcceptsOrders($visit);

            $request = LabRequest::query()->create([
                'facility_id' => $visit->facility_id,
                'visit_id' => $visit->id,
                'ordered_by' => $actor->id,
                'status' => 'ordered',
                'clinical_notes' => $notes,
            ]);

            $tests = LabTest::query()->whereIn('id', $testIds)->where('is_active', true)->get();

            if ($tests->count() !== count(array_unique($testIds))) {
                throw ValidationException::withMessages([
                    'test_ids' => 'One or more lab tests are not available at this facility.',
                ]);
            }

            foreach ($tests as $test) {
                $item = LabRequestItem::query()->create([
                    'facility_id' => $visit->facility_id,
                    'lab_request_id' => $request->id,
                    'lab_test_id' => $test->id,
                    'status' => 'ordered',
                ]);

                $price = $this->prices->forService($test->service, $visit);
                $line = $this->billing->charge($visit, $test->name, 'lab_request_item', $item->id, 1, $price);
                $item->update(['bill_line_id' => $line->id]);
            }

            $this->queues->enqueue($visit, QueueStage::Laboratory);

            return $request;
        });
    }

    public function verifyLabResult(LabRequestItem $item, User $actor, string $value): LabRequestItem
    {
        return DB::transaction(function () use ($item, $actor, $value): LabRequestItem {
            $item = LabRequestItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if (! in_array($item->status, ['ordered', 'resulted'], true)) {
                throw ValidationException::withMessages([
                    'result_value' => "This test is {$item->status} and cannot take a result.",
                ]);
            }

            $test = $item->test()->with('reagent')->first();

            if ($test?->reagent_item_id && $test->reagent) {
                $location = StockLocation::query()
                    ->where('facility_id', $item->facility_id)
                    ->where('kind', 'laboratory')
                    ->first();

                if (! $location) {
                    throw ValidationException::withMessages([
                        'result_value' => 'No laboratory store is set up, so the reagent cannot be consumed.',
                    ]);
                }

                $item->loadMissing('request.visit');

                $this->stock->consume(
                    $location,
                    $test->reagent,
                    max(1, (int) $test->reagent_quantity),
                    StockReason::LabUse,
                    $item,
                    $item->request->visit->patient_id,
                );
            }

            $item->update([
                'result_value' => $value,
                'result_flag' => 'recorded',
                'status' => 'verified',
                'resulted_by' => $actor->id,
                'verified_by' => $actor->id,
                'resulted_at' => now(),
                'verified_at' => now(),
            ]);

            $pending = LabRequestItem::query()
                ->where('lab_request_id', $item->lab_request_id)
                ->where('status', '!=', 'verified')
                ->exists();

            $item->request()->update(['status' => $pending ? 'in_progress' : 'verified']);

            if (! $pending) {
                $visit = $item->request->visit;
                $this->queues->complete($visit, QueueStage::Laboratory);
            }

            return $item->fresh();
        });
    }

    public function cancelLabItem(LabRequestItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $item = LabRequestItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($item->status !== 'ordered') {
                throw ValidationException::withMessages([
                    'laboratory' => 'Only a test that has no result yet can be cancelled.',
                ]);
            }

            if ($item->bill_line_id) {
                $line = $item->billLine()->with('bill.payments')->first();

                if ($line && $line->bill->payments->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'laboratory' => 'This test already has a payment and cannot be cancelled here.',
                    ]);
                }

                $line?->delete();
            }

            $item->update(['status' => 'cancelled']);
        });
    }

    public function orderImaging(Visit $visit, User $actor, ImagingStudy $study, ?string $notes = null): ImagingOrder
    {
        return DB::transaction(function () use ($visit, $actor, $study, $notes): ImagingOrder {
            $this->assertAcceptsOrders($visit);

            $order = ImagingOrder::query()->create([
                'facility_id' => $visit->facility_id,
                'visit_id' => $visit->id,
                'imaging_study_id' => $study->id,
                'ordered_by' => $actor->id,
                'status' => 'ordered',
                'clinical_notes' => $notes,
            ]);

            $price = $this->prices->forService($study->service, $visit);
            $line = $this->billing->charge($visit, $study->name, 'imaging_order', $order->id, 1, $price);
            $order->update(['bill_line_id' => $line->id]);
            $this->queues->enqueue($visit, QueueStage::Radiology);

            return $order;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reportImaging(ImagingOrder $order, User $actor, array $data): ImagingOrder
    {
        $order->update([
            'report' => $data['report'],
            'image_path' => $data['image_path'] ?? $order->image_path,
            'status' => 'reported',
            'reported_by' => $actor->id,
            'reported_at' => now(),
        ]);

        $visit = $order->visit;
        $stillOpen = ImagingOrder::query()
            ->where('visit_id', $visit->id)
            ->where('status', 'ordered')
            ->exists();

        if (! $stillOpen) {
            $this->queues->complete($visit, QueueStage::Radiology);
        }

        return $order;
    }

    /**
     * @param  list<array{item_id: int, dose: string, frequency: string, duration_days: int, quantity: int}>  $lines
     */
    public function prescribe(Visit $visit, User $actor, array $lines, ?string $notes = null): Prescription
    {
        return DB::transaction(function () use ($visit, $actor, $lines, $notes): Prescription {
            $this->assertAcceptsOrders($visit);

            $prescription = Prescription::query()->create([
                'facility_id' => $visit->facility_id,
                'visit_id' => $visit->id,
                'clinician_id' => $actor->id,
                'status' => 'pending',
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                PrescriptionItem::query()->create([
                    'facility_id' => $visit->facility_id,
                    'prescription_id' => $prescription->id,
                    'item_id' => $line['item_id'],
                    'dose' => $line['dose'],
                    'frequency' => $line['frequency'],
                    'duration_days' => $line['duration_days'],
                    'quantity' => $line['quantity'],
                    'status' => 'pending',
                ]);
            }

            $this->queues->enqueue($visit, QueueStage::Pharmacy);

            return $prescription;
        });
    }

    public function admit(Visit $visit, User $actor, Bed $bed, string $diagnosis): Admission
    {
        return DB::transaction(function () use ($visit, $actor, $bed, $diagnosis): Admission {
            $this->assertAcceptsOrders($visit);
            $bed = Bed::query()->whereKey($bed->id)->lockForUpdate()->firstOrFail();

            if ($bed->status !== 'available') {
                throw ValidationException::withMessages([
                    'bed_id' => 'That bed is not available.',
                ]);
            }

            if ($visit->admission()->where('status', 'admitted')->exists()) {
                throw ValidationException::withMessages([
                    'bed_id' => 'This visit already has an open admission.',
                ]);
            }

            $bed->update(['status' => 'occupied']);

            $admission = Admission::query()->create([
                'facility_id' => $visit->facility_id,
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'ward_id' => $bed->ward_id,
                'bed_id' => $bed->id,
                'admitted_by' => $actor->id,
                'admitted_at' => now(),
                'diagnosis_summary' => $diagnosis,
                'status' => 'admitted',
            ]);

            $this->chargeService($visit, 'BED', 'admission', $admission->id, 'Bed day');

            $visit->update(['visit_type' => VisitType::Inpatient]);
            $visit->moveTo(VisitStatus::Admitted);

            return $admission;
        });
    }

    public function discharge(Admission $admission, User $actor, string $summary, string $outcome): Admission
    {
        return DB::transaction(function () use ($admission, $summary, $outcome): Admission {
            $visit = $admission->visit()->with(['bill.lines', 'bill.payments', 'prescriptions.items'])->first();

            $this->assertReadyToLeave($visit);

            $admission->update([
                'status' => 'discharged',
                'discharged_at' => now(),
                'discharge_summary' => $summary,
                'discharge_outcome' => $outcome,
            ]);

            $admission->bed()->update(['status' => 'cleaning']);

            $visit->moveTo(VisitStatus::Discharged);
            $this->closeIfSettled($visit->fresh());

            return $admission->fresh();
        });
    }

    public function completeOutpatient(Visit $visit): Visit
    {
        return DB::transaction(function () use ($visit): Visit {
            if ($visit->status === VisitStatus::Admitted) {
                throw ValidationException::withMessages([
                    'visit' => 'Admitted patients leave through discharge.',
                ]);
            }

            $visit->load(['bill.lines', 'bill.payments', 'prescriptions.items']);
            $this->assertReadyToLeave($visit);

            if ($visit->status->canMoveTo(VisitStatus::Discharged) && $visit->status !== VisitStatus::Discharged) {
                $visit->moveTo(VisitStatus::Discharged);
            }

            $this->closeIfSettled($visit->fresh());

            return $visit->fresh();
        });
    }

    public function closeIfSettled(Visit $visit): void
    {
        $visit->loadMissing(['bill.lines', 'bill.payments']);

        if ($visit->status !== VisitStatus::Discharged) {
            return;
        }

        if ($visit->payer->settlesAtCashier() && $visit->bill && $visit->bill->cashBalance() > 0) {
            return;
        }

        $visit->update([
            'status' => VisitStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    /**
     * Orders on a visit the patient has already left would reopen a settled bill.
     */
    private function assertAcceptsOrders(Visit $visit): void
    {
        $status = Visit::query()->whereKey($visit->id)->lockForUpdate()->value('status');

        if (in_array($status, [VisitStatus::Discharged, VisitStatus::Closed], true)) {
            throw ValidationException::withMessages([
                'visit' => "Visit {$visit->visit_number} is {$status->label()} and cannot take new orders. Open a new visit.",
            ]);
        }
    }

    private function assertReadyToLeave(Visit $visit): void
    {
        if ($visit->payer->settlesAtCashier() && $visit->bill && $visit->bill->cashBalance() > 0) {
            throw ValidationException::withMessages([
                'bill' => 'The cash balance must be paid or waived before the patient leaves.',
            ]);
        }

        $pendingMeds = PrescriptionItem::query()
            ->whereIn('prescription_id', $visit->prescriptions->pluck('id'))
            ->where('status', 'pending')
            ->exists();

        if ($pendingMeds) {
            throw ValidationException::withMessages([
                'pharmacy' => 'Pending prescriptions must be dispensed or cancelled first.',
            ]);
        }

        $pendingLabs = LabRequestItem::query()
            ->whereIn('lab_request_id', $visit->labRequests()->select('id'))
            ->whereNotIn('status', ['verified', 'cancelled'])
            ->exists();

        if ($pendingLabs) {
            throw ValidationException::withMessages([
                'laboratory' => 'Laboratory results must be verified or the order cancelled first.',
            ]);
        }
    }

    private function chargeService(Visit $visit, string $code, string $sourceType, int $sourceId, string $label, bool $exemptPreauth = false): void
    {
        $service = Service::query()
            ->where('facility_id', $visit->facility_id)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $service) {
            throw ValidationException::withMessages([
                'service' => "Price list is missing the {$code} service. An administrator needs to add it.",
            ]);
        }

        $this->billing->charge(
            $visit,
            $label,
            $sourceType,
            $sourceId,
            1,
            $this->prices->forService($service, $visit),
            $exemptPreauth,
        );
    }
}
