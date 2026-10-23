<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->permits('patients.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'surname' => ['required', 'string', 'max:100'],
            'given_name' => ['required', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', Rule::enum(Sex::class)],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:40'],
            'district' => ['required', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'next_of_kin_name' => ['required', 'string', 'max:150'],
            'next_of_kin_relationship' => ['required', 'string', 'max:50'],
            'next_of_kin_phone' => ['required', 'string', 'max:30'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'allergies' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'confirm_duplicate' => ['sometimes', 'boolean'],
        ];
    }
}
