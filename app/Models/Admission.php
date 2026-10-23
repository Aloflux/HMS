<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Admission extends Model
{
    use BelongsToFacility, RecordsAudit;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'visit_id', 'patient_id', 'ward_id', 'bed_id', 'admitted_by', 'admitted_at', 'discharged_at', 'discharge_summary', 'discharge_outcome', 'diagnosis_summary', 'status',
    ];

    protected function casts(): array
    {
        return [
            'admitted_at' => 'datetime',
            'discharged_at' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return $this->status === 'admitted';
    }

    /**
     * Ward entries on a discharged patient would chart care, or charge a bill, after they left.
     */
    public function ensureOpen(): void
    {
        if (! $this->isOpen()) {
            throw ValidationException::withMessages([
                'admission' => 'This patient was discharged '.($this->discharged_at?->format('d M Y H:i') ?? '').'. The ward record is closed.',
            ]);
        }
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function nursingNotes(): HasMany
    {
        return $this->hasMany(NursingNote::class);
    }

    public function administrations(): HasMany
    {
        return $this->hasMany(MedicationAdministration::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(WardConsumption::class);
    }
}
