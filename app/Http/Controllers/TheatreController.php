<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\TheatreBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TheatreController extends Controller
{
    public function index(): View
    {
        return view('theatre.index', [
            'bookings' => TheatreBooking::query()
                ->with(['patient', 'surgeon', 'note'])
                ->orderByRaw("CASE WHEN status = 'booked' THEN 0 ELSE 1 END")
                ->orderBy('scheduled_at')
                ->limit(40)
                ->get(),
            'patients' => Patient::query()->latest()->limit(200)->get(['patient_number', 'surname', 'given_name', 'other_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_number' => ['required', 'string', Rule::exists('patients', 'patient_number')->where('facility_id', $request->user()->facility_id)],
            'procedure' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'patient_number.exists' => 'No patient at this facility has that number.',
        ]);

        TheatreBooking::query()->create([
            'patient_id' => Patient::query()->where('patient_number', $data['patient_number'])->value('id'),
            'procedure' => $data['procedure'],
            'scheduled_at' => $data['scheduled_at'],
            'facility_id' => $request->user()->facility_id,
            'surgeon_id' => $request->user()->id,
            'status' => 'booked',
        ]);

        return back()->with('status', 'Theatre booking saved.');
    }

    public function note(Request $request, TheatreBooking $booking): RedirectResponse
    {
        $data = $request->validate([
            'findings' => ['nullable', 'string', 'max:5000'],
            'procedure_notes' => ['required', 'string', 'max:5000'],
        ]);

        $booking->note()->updateOrCreate(
            ['theatre_booking_id' => $booking->id],
            [
                'facility_id' => $booking->facility_id,
                'findings' => $data['findings'] ?? null,
                'procedure_notes' => $data['procedure_notes'],
                'surgeon_id' => $request->user()->id,
                'recorded_at' => now(),
            ],
        );

        $booking->update(['status' => 'completed']);

        return back()->with('status', 'Operation note saved.');
    }
}
