<?php

namespace App\Models;

use App\Enums\Payer;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Concerns\BelongsToFacility;
use App\Models\Concerns\RecordsAudit;
use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use BelongsToFacility, HasFactory, RecordsAudit;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'patient_id',
        'visit_number',
        'visit_type',
        'status',
        'priority',
        'department_id',
        'clinician_id',
        'payer',
        'insurance_scheme_id',
        'member_number',
        'chief_complaint',
        'opened_at',
        'closed_at',
        'opened_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_type' => VisitType::class,
            'status' => VisitStatus::class,
            'priority' => Priority::class,
            'payer' => Payer::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function clinician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinician_id');
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(InsuranceScheme::class, 'insurance_scheme_id');
    }

    public function triageRecords(): HasMany
    {
        return $this->hasMany(TriageRecord::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class);
    }

    public function imagingOrders(): HasMany
    {
        return $this->hasMany(ImagingOrder::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }

    public function admission(): HasOne
    {
        return $this->hasOne(Admission::class);
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(QueueEntry::class);
    }

    public function preauthorisations(): HasMany
    {
        return $this->hasMany(Preauthorisation::class);
    }

    public function moveTo(VisitStatus $status): void
    {
        if (! $this->status->canMoveTo($status)) {
            throw ValidationException::withMessages([
                'status' => "This visit cannot move from {$this->status->value} to {$status->value}.",
            ]);
        }

        $this->update(['status' => $status]);
    }
}
