<?php

namespace App\Http\Controllers;

use App\Enums\Payer;
use App\Enums\Priority;
use App\Enums\VisitType;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Services\EncounterService;
use App\Services\PatientRegistrar;
use App\Services\SmsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        return view('appointments.index', [
            'appointments' => Appointment::query()->with(['patient', 'department'])->orderBy('scheduled_at')->paginate(20),
            'patientChoices' => $this->patientChoices(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAppointmentRequest $request, SmsNotifier $sms, PatientRegistrar $registrar): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        [$appointment, $patient, $registered] = DB::transaction(function () use ($data, $user, $registrar): array {
            $registered = $data['booking_for'] === 'new';

            if ($registered) {
                $patient = $registrar->register($user->facility, $user, [
                    'surname' => $data['surname'],
                    'given_name' => $data['given_name'],
                    'sex' => $data['sex'],
                    'date_of_birth' => $data['date_of_birth'],
                    'phone' => $data['phone'],
                    'national_id' => $data['national_id'] ?? null,
                    'district' => $data['district'],
                    'next_of_kin_name' => $data['next_of_kin_name'],
                    'next_of_kin_relationship' => $data['next_of_kin_relationship'],
                    'next_of_kin_phone' => $data['next_of_kin_phone'],
                    'allergies' => $data['allergies'] ?? null,
                    'confirm_duplicate' => (bool) ($data['confirm_duplicate'] ?? false),
                ]);
            } else {
                $patient = Patient::query()->findOrFail($data['patient_id']);
            }

            $appointment = Appointment::query()->create([
                'facility_id' => $user->facility_id,
                'patient_id' => $patient->id,
                'department_id' => $data['department_id'],
                'scheduled_at' => $data['scheduled_at'],
                'reason' => $data['reason'] ?? null,
                'status' => 'booked',
            ]);

            return [$appointment, $patient, $registered];
        });
        $sms->queue(
            $patient,
            'appointment',
            'Appointment at '.$appointment->scheduled_at->format('d M Y H:i').($appointment->reason ? ' for '.$appointment->reason : '').'.',
        );
        $appointment->update(['reminder_queued_at' => now()]);

        $status = $registered
            ? "{$patient->fullName()} registered as {$patient->patient_number} and booked. Reminder queued."
            : 'Appointment booked and reminder queued.';

        return back()->with('status', $status);
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

    /**
     * @return list<array{id: int, name: string, phone: string, number: string}>
     */
    private function patientChoices(): array
    {
        return Patient::query()
            ->orderBy('surname')
            ->orderBy('given_name')
            ->get(['id', 'surname', 'given_name', 'other_name', 'phone', 'patient_number'])
            ->map(fn (Patient $patient): array => [
                'id' => $patient->id,
                'name' => $patient->fullName(),
                'phone' => $patient->phone,
                'number' => $patient->patient_number,
            ])
            ->all();
    }
}
