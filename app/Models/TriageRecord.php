<?php

namespace App\Models;

use App\Enums\Priority;
use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriageRecord extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'visit_id',
        'temperature',
        'pulse',
        'respiration',
        'systolic',
        'diastolic',
        'spo2',
        'weight_kg',
        'height_cm',
        'priority',
        'chief_complaint',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'temperature' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:1',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
