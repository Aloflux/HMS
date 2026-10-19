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
            'bookings' => TheatreBooking::query()->with(['patient', 'surgeon', 'note'])->orderBy('scheduled_at')->limit(40)->get(),
            'patients' => Patient::query()->orderBy('surname')->limit(200)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('facility_id', $request->user()->facility_id)],
            'procedure' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
        ]);

        TheatreBooking::query()->create([
            ...$data,
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
