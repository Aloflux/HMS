<?php

namespace App\Models;

use App\Enums\Sex;
use App\Models\Concerns\BelongsToFacility;
use App\Models\Concerns\RecordsAudit;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use BelongsToFacility, HasFactory, RecordsAudit;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'patient_number',
        'surname',
        'given_name',
        'other_name',
        'sex',
        'date_of_birth',
        'phone',
        'national_id',
        'district',
        'village',
        'next_of_kin_name',
        'next_of_kin_relationship',
        'next_of_kin_phone',
        'blood_group',
        'allergies',
        'photo_path',
        'consent_at',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'date_of_birth' => 'date',
            'consent_at' => 'datetime',
        ];
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function latestVisit(): HasOne
    {
        return $this->hasOne(Visit::class)->latestOfMany('opened_at');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->surname,
            $this->given_name,
            $this->other_name,
        ])));
    }

    public function ageInYears(): int
    {
        return $this->date_of_birth->age;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', $this->fullName()))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
