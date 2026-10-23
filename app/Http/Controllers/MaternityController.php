<?php

namespace App\Http\Controllers;

use App\Enums\Sex;
use App\Models\AntenatalVisit;
use App\Models\Delivery;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaternityController extends Controller
{
    public function index(): View
    {
        return view('maternity.index', [
            'antenatal' => AntenatalVisit::query()->with('patient')->latest('seen_at')->limit(30)->get(),
            'deliveries' => Delivery::query()->with('patient')->latest('delivered_at')->limit(30)->get(),
            'patients' => Patient::query()->where('sex', Sex::Female)->latest()->limit(200)->get(['patient_number', 'surname', 'given_name', 'other_name']),
        ]);
    }

    public function antenatal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gestation_weeks' => ['required', 'integer', 'min:1', 'max:45'],
            'fundal_height_cm' => ['nullable', 'integer', 'min:1', 'max:50'],
            'fetal_heart' => ['nullable', 'string', 'max:40'],
            'blood_pressure' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        AntenatalVisit::query()->create([
            ...$data,
            'patient_id' => $this->mother($request),
            'facility_id' => $request->user()->facility_id,
            'recorded_by' => $request->user()->id,
            'seen_at' => now(),
        ]);

        return back()->with('status', 'Antenatal visit recorded.');
    }

    public function delivery(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'delivered_at' => ['required', 'date'],
            'mode' => ['required', Rule::in(['svd', 'caesarean', 'assisted'])],
            'outcome' => ['required', Rule::in(['live', 'stillbirth'])],
            'baby_sex' => ['nullable', Rule::in(['male', 'female'])],
            'birth_weight_kg' => ['nullable', 'numeric', 'min:0.3', 'max:7'],
            'apgar' => ['nullable', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Delivery::query()->create([
            ...$data,
            'patient_id' => $this->mother($request),
            'facility_id' => $request->user()->facility_id,
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Delivery recorded.');
    }

    /**
     * Staff pick the mother by patient number, so any patient on the register can be found.
     */
    private function mother(Request $request): int
    {
        $data = $request->validate([
            'patient_number' => ['required', 'string', 'max:40'],
        ]);

        $patient = Patient::query()->where('patient_number', trim($data['patient_number']))->first();

        if (! $patient) {
            throw ValidationException::withMessages([
                'patient_number' => 'No patient at this facility has that number.',
            ]);
        }

        if ($patient->sex !== Sex::Female) {
            throw ValidationException::withMessages([
                'patient_number' => "{$patient->fullName()} is not registered as female.",
            ]);
        }

        return $patient->id;
    }
}
