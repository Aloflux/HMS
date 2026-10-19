<?php

namespace App\Http\Controllers;

use App\Enums\BillingType;
use App\Enums\Payer;
use App\Enums\Priority;
use App\Enums\VisitType;
use App\Models\Bed;
use App\Models\Icd10Code;
use App\Models\ImagingStudy;
use App\Models\Item;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\EncounterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function __construct(private EncounterService $encounters) {}

    public function show(Visit $visit): View
    {
        $visit->load([
            'patient',
            'scheme',
            'department',
            'triageRecords.recorder',
            'consultations.diagnoses',
            'consultations.clinician',
            'labRequests.items.test',
            'imagingOrders.study',
            'prescriptions.items.item',
            'prescriptions.items.dispensations',
            'bill.lines',
            'bill.payments',
            'bill.waivers',
            'admission.bed.ward',
            'admission.nursingNotes.recorder',
            'preauthorisations',
        ]);

        return view('visits.show', [
            'visit' => $visit,
            'icd10' => Icd10Code::query()->orderBy('code')->get(),
            'labTests' => LabTest::query()->with('service')->where('is_active', true)->orderBy('name')->get(),
            'studies' => ImagingStudy::query()->with('service')->where('is_active', true)->orderBy('name')->get(),
            'medicines' => Item::query()->where('billing_type', BillingType::BillableMedicine)->where('is_active', true)->orderBy('name')->get(),
            'beds' => Bed::query()->with('ward')->where('status', 'available')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;

        $data = $request->validate([
            'visit_type' => ['required', Rule::enum(VisitType::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'payer' => ['required', Rule::enum(Payer::class)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('facility_id', $facilityId)],
            'insurance_scheme_id' => ['nullable', 'required_unless:payer,cash', Rule::exists('insurance_schemes', 'id')->where('facility_id', $facilityId)],
            'member_number' => ['nullable', 'required_unless:payer,cash', 'string', 'max:60'],
            'chief_complaint' => ['nullable', 'string', 'max:255'],
        ]);

        $visit = $this->encounters->openVisit($patient, $request->user(), $data);

        return redirect()->route('visits.show', $visit)->with('status', 'Visit '.$visit->visit_number.' opened.');
    }

    public function triage(Request $request, Visit $visit): RedirectResponse
    {
        $data = $request->validate([
            'temperature' => ['nullable', 'numeric', 'between:30,45'],
            'pulse' => ['nullable', 'integer', 'between:20,250'],
            'respiration' => ['nullable', 'integer', 'between:5,80'],
            'systolic' => ['nullable', 'integer', 'between:40,300'],
            'diastolic' => ['nullable', 'integer', 'between:20,200'],
            'spo2' => ['nullable', 'integer', 'between:1,100'],
            'weight_kg' => ['nullable', 'numeric', 'between:0.5,400'],
            'height_cm' => ['nullable', 'numeric', 'between:20,250'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'chief_complaint' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->encounters->recordTriage($visit, $request->user(), $data);

        return back()->with('status', 'Triage recorded.');
    }

    public function consult(Request $request, Visit $visit): RedirectResponse
    {
        $data = $request->validate([
            'history' => ['nullable', 'string', 'max:5000'],
            'examination' => ['nullable', 'string', 'max:5000'],
            'plan' => ['nullable', 'string', 'max:5000'],
            'referral' => ['nullable', 'string', 'max:5000'],
            'icd10_codes' => ['required', 'array', 'min:1'],
            'icd10_codes.*' => ['required', 'string', 'exists:icd10_codes,code'],
        ]);

        $this->encounters->recordConsultation($visit, $request->user(), $data);

        return back()->with('status', 'Consultation saved and charged.');
    }

    public function orderLab(Request $request, Visit $visit): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['integer', Rule::exists('lab_tests', 'id')->where('facility_id', $facilityId)],
            'clinical_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->encounters->orderLabs($visit, $request->user(), $data['test_ids'], $data['clinical_notes'] ?? null);

        return back()->with('status', 'Laboratory order sent and charged.');
    }

    public function orderImaging(Request $request, Visit $visit): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'imaging_study_id' => ['required', Rule::exists('imaging_studies', 'id')->where('facility_id', $facilityId)],
            'clinical_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $study = ImagingStudy::query()->findOrFail($data['imaging_study_id']);
        $this->encounters->orderImaging($visit, $request->user(), $study, $data['clinical_notes'] ?? null);

        return back()->with('status', 'Imaging order sent and charged.');
    }

    public function prescribe(Request $request, Visit $visit): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', Rule::exists('items', 'id')->where('facility_id', $facilityId)],
            'lines.*.dose' => ['required', 'string', 'max:60'],
            'lines.*.frequency' => ['required', 'string', 'max:60'],
            'lines.*.duration_days' => ['required', 'integer', 'min:1', 'max:90'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $this->encounters->prescribe($visit, $request->user(), $data['lines'], $data['notes'] ?? null);

        return back()->with('status', 'Prescription written. Stock moves only when the pharmacist dispenses.');
    }

    public function admit(Request $request, Visit $visit): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'bed_id' => ['required', Rule::exists('beds', 'id')->where('facility_id', $facilityId)],
            'diagnosis_summary' => ['required', 'string', 'max:255'],
        ]);

        $bed = Bed::query()->findOrFail($data['bed_id']);
        $this->encounters->admit($visit, $request->user(), $bed, $data['diagnosis_summary']);

        return back()->with('status', 'Patient admitted.');
    }

    public function complete(Request $request, Visit $visit): RedirectResponse
    {
        $this->encounters->completeOutpatient($visit);

        return back()->with('status', 'Visit completed.');
    }
}
