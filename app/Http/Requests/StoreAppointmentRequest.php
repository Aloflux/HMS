<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->permits('appointments.manage')) {
            return false;
        }

        if ($this->string('booking_for')->toString() === 'new') {
            return $user->permits('patients.manage');
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $facilityId = $this->user()?->facility_id;
        $registering = $this->string('booking_for')->toString() === 'new';

        return [
            'booking_for' => ['required', Rule::in(['existing', 'new'])],
            'patient_id' => [
                Rule::excludeIf($registering),
                'required',
                Rule::exists('patients', 'id')->where('facility_id', $facilityId),
            ],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('facility_id', $facilityId)],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'reason' => ['nullable', 'string', 'max:255'],
            'surname' => [Rule::excludeUnless($registering), 'required', 'string', 'max:100'],
            'given_name' => [Rule::excludeUnless($registering), 'required', 'string', 'max:100'],
            'sex' => [Rule::excludeUnless($registering), 'required', Rule::enum(Sex::class)],
            'date_of_birth' => [Rule::excludeUnless($registering), 'required', 'date', 'before:today'],
            'phone' => [Rule::excludeUnless($registering), 'required', 'string', 'max:30'],
            'national_id' => [Rule::excludeUnless($registering), 'nullable', 'string', 'max:40'],
            'district' => [Rule::excludeUnless($registering), 'required', 'string', 'max:100'],
            'next_of_kin_name' => [Rule::excludeUnless($registering), 'required', 'string', 'max:150'],
            'next_of_kin_relationship' => [Rule::excludeUnless($registering), 'required', 'string', 'max:50'],
            'next_of_kin_phone' => [Rule::excludeUnless($registering), 'required', 'string', 'max:30'],
            'allergies' => [Rule::excludeUnless($registering), 'nullable', 'string', 'max:500'],
            'consent' => [Rule::excludeUnless($registering), 'accepted'],
            'confirm_duplicate' => [Rule::excludeUnless($registering), 'sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Search for the patient, or register them in this form.',
            'consent.accepted' => 'Consent is required under the Data Protection and Privacy Act.',
        ];
    }
}
