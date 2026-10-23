<?php

namespace App\Http\Controllers;

use App\Enums\Payer;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Admission;
use App\Models\InsuranceScheme;
use App\Models\LabRequestItem;
use App\Models\Patient;
use App\Models\PrescriptionItem;
use App\Models\Visit;
use App\Services\PatientRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $care = $request->string('care')->toString();
        $sort = $request->string('sort')->toString() === 'name' ? 'name' : 'latest';

        if (! in_array($care, ['inpatient', 'outpatient'], true)) {
            $care = 'all';
        }

        $patients = Patient::query()
            ->with('latestVisit.clinician')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('patient_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('given_name', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%");
                });
            })
            ->when($care === 'inpatient', function ($query): void {
                $query->whereHas('visits', fn ($visits) => $visits->where('status', VisitStatus::Admitted));
            })
            ->when($care === 'outpatient', function ($query): void {
                $query->whereHas('visits', fn ($visits) => $visits->where('visit_type', VisitType::Outpatient))
                    ->whereDoesntHave('visits', fn ($visits) => $visits->where('status', VisitStatus::Admitted));
            })
            ->when(
                $sort === 'name',
                fn ($query) => $query->orderBy('surname')->orderBy('given_name'),
                fn ($query) => $query->latest(),
            )
            ->paginate(15)
            ->withQueryString();

        $seen = (int) Visit::query()->distinct()->count('patient_id');
        $insured = (int) Visit::query()->where('payer', Payer::Insurance)->distinct()->count('patient_id');

        return view('patients.index', [
            'patients' => $patients,
            'search' => $search,
            'care' => $care,
            'sort' => $sort,
            'totalPatients' => Patient::query()->count(),
            'inpatients' => Admission::query()->where('status', 'admitted')->count(),
            'openedToday' => Visit::query()->where('opened_at', '>=', now()->startOfDay())->count(),
            'insuranceRate' => $seen > 0 ? (int) round(($insured / $seen) * 100) : 0,
        ]);
    }

    public function create(): View
    {
        return view('patients.create');
    }

    public function store(StorePatientRequest $request, PatientRegistrar $registrar): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('patient-photos', 'local');
        }

        $patient = $registrar->register($request->user()->facility, $request->user(), $data);

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', "{$patient->fullName()} registered as {$patient->patient_number}.");
    }

    public function edit(Patient $patient): View
    {
        return view('patients.edit', [
            'patient' => $patient,
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient, PatientRegistrar $registrar): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'confirm_duplicate']);
        $data['confirm_duplicate'] = $request->boolean('confirm_duplicate');
        $duplicates = $registrar->duplicates($request->user()->facility, $data)
            ->reject(fn (Patient $match): bool => $match->is($patient));

        if ($duplicates->isNotEmpty() && ! $data['confirm_duplicate']) {
            throw ValidationException::withMessages([
                'confirm_duplicate' => 'Another patient already has this phone, name and date of birth ('.$duplicates->first()->patient_number.'). Confirm to save anyway.',
            ]);
        }

        if (! empty($data['national_id'])) {
            $nationalIdMatch = Patient::query()
                ->where('facility_id', $request->user()->facility_id)
                ->where('national_id', $data['national_id'])
                ->whereKeyNot($patient->id)
                ->first();

            if ($nationalIdMatch && ! $data['confirm_duplicate']) {
                throw ValidationException::withMessages([
                    'national_id' => 'National ID is already used by '.$nationalIdMatch->patient_number.'.',
                ]);
            }
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('patient-photos', 'local');
        }

        unset($data['confirm_duplicate']);
        $patient->update($data);

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', "{$patient->fullName()} updated.");
    }

    public function show(Patient $patient): View
    {
        $patient->load([
            'visits' => fn ($query) => $query->with('clinician')->latest('opened_at'),
        ]);

        return view('patients.show', [
            'patient' => $patient,
            'latestVisit' => $patient->visits->first(),
            'departments' => $patient->facility->departments()->where('is_active', true)->orderBy('name')->get(),
            'schemes' => InsuranceScheme::query()->where('is_active', true)->orderBy('name')->get(),
            'labOrders' => LabRequestItem::query()
                ->where('status', 'ordered')
                ->whereHas('request.visit', fn ($query) => $query->where('patient_id', $patient->id))
                ->with('test')
                ->latest()
                ->limit(4)
                ->get(),
            'pendingMedicines' => PrescriptionItem::query()
                ->where('status', 'pending')
                ->whereHas('prescription.visit', fn ($query) => $query->where('patient_id', $patient->id))
                ->with('item')
                ->latest()
                ->limit(4)
                ->get(),
            'recentPatients' => Patient::query()
                ->whereKeyNot($patient->id)
                ->with('latestVisit')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function photo(Patient $patient): StreamedResponse
    {
        abort_unless($patient->photo_path && Storage::disk('local')->exists($patient->photo_path), 404);

        return Storage::disk('local')->response($patient->photo_path);
    }
}
