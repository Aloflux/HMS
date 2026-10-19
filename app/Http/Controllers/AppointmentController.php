<?php

namespace App\Http\Controllers;

use App\Enums\Payer;
use App\Enums\Priority;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Services\EncounterService;
use App\Services\SmsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        return view('appointments.index', [
            'appointments' => Appointment::query()->with(['patient', 'department'])->orderBy('scheduled_at')->paginate(20),
            'patients' => Patient::query()->orderBy('surname')->limit(200)->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, SmsNotifier $sms): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('facility_id', $facilityId)],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('facility_id', $facilityId)],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $appointment = Appointment::query()->create([
            ...$data,
            'facility_id' => $facilityId,
            'status' => 'booked',
        ]);

        $patient = Patient::query()->findOrFail($data['patient_id']);
        $sms->queue(
            $patient,
            'appointment',
            'Appointment at '.$appointment->scheduled_at->format('d M Y H:i').($appointment->reason ? ' for '.$appointment->reason : '').'.',
        );
        $appointment->update(['reminder_queued_at' => now()]);

        return back()->with('status', 'Appointment booked and reminder queued.');
    }

    public function checkIn(Request $request, Appointment $appointment, EncounterService $encounters): RedirectResponse
    {
        if ($appointment->status !== 'booked') {
            return back()->with('error', 'Only a booked appointment can be checked in.');
        }

        $visit = $encounters->openVisit($appointment->patient, $request->user(), [
            'visit_type' => VisitType::Outpatient,
            'priority' => Priority::Routine,
            'payer' => Payer::Cash,
            'department_id' => $appointment->department_id,
            'chief_complaint' => $appointment->reason,
        ]);

        $appointment->update(['status' => 'checked_in']);

        return redirect()->route('visits.show', $visit)->with('status', 'Patient checked in.');
    }
}
