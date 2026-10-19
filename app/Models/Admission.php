<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
