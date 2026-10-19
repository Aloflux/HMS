<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Models\InsuranceScheme;
use App\Models\Patient;
use App\Services\PatientRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $patients = Patient::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('patient_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('given_name', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('patients.index', [
            'patients' => $patients,
            'search' => $search,
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

    public function show(Patient $patient): View
    {
        $patient->load([
            'visits' => fn ($query) => $query->latest('opened_at'),
            'appointments' => fn ($query) => $query->latest('scheduled_at')->limit(5),
        ]);

        return view('patients.show', [
            'patient' => $patient,
            'departments' => $patient->facility->departments()->where('is_active', true)->orderBy('name')->get(),
            'schemes' => InsuranceScheme::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function photo(Patient $patient): StreamedResponse
    {
        abort_unless($patient->photo_path && Storage::disk('local')->exists($patient->photo_path), 404);

        return Storage::disk('local')->response($patient->photo_path);
    }
}
