<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PatientRegistrar
{
    public function __construct(private SequenceGenerator $sequences) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(Facility $facility, User $actor, array $data): Patient
    {
        $duplicates = $this->duplicates($facility, $data);

        if ($duplicates->isNotEmpty() && ! ($data['confirm_duplicate'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm_duplicate' => 'A patient with the same phone, name and date of birth already exists ('.$duplicates->first()->patient_number.'). Confirm to register anyway.',
            ]);
        }

        if (! empty($data['national_id'])) {
            $nationalIdMatch = Patient::query()
                ->where('facility_id', $facility->id)
                ->where('national_id', $data['national_id'])
                ->first();

            if ($nationalIdMatch && ! ($data['confirm_duplicate'] ?? false)) {
                throw ValidationException::withMessages([
                    'national_id' => 'National ID is already used by '.$nationalIdMatch->patient_number.'.',
                ]);
            }
        }

        return Patient::query()->create([
            'facility_id' => $facility->id,
            'patient_number' => $this->sequences->next($facility, 'patient'),
            'surname' => $data['surname'],
            'given_name' => $data['given_name'],
            'other_name' => $data['other_name'] ?? null,
            'sex' => $data['sex'],
            'date_of_birth' => $data['date_of_birth'],
            'phone' => $data['phone'],
            'national_id' => $data['national_id'] ?? null,
            'district' => $data['district'],
            'village' => $data['village'] ?? null,
            'next_of_kin_name' => $data['next_of_kin_name'],
            'next_of_kin_relationship' => $data['next_of_kin_relationship'],
            'next_of_kin_phone' => $data['next_of_kin_phone'],
            'blood_group' => $data['blood_group'] ?? null,
            'allergies' => $data['allergies'] ?? null,
            'photo_path' => $data['photo_path'] ?? null,
            'consent_at' => now(),
            'registered_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Patient>
     */
    public function duplicates(Facility $facility, array $data): Collection
    {
        return Patient::query()
            ->where('facility_id', $facility->id)
            ->where('phone', $data['phone'])
            ->whereDate('date_of_birth', $data['date_of_birth'])
            ->whereRaw('lower(surname) = ?', [mb_strtolower($data['surname'])])
            ->whereRaw('lower(given_name) = ?', [mb_strtolower($data['given_name'])])
            ->get();
    }
}
