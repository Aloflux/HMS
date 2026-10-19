<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntenatalVisit extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'patient_id', 'visit_id', 'gestation_weeks', 'fundal_height_cm', 'fetal_heart', 'blood_pressure', 'notes', 'recorded_by', 'seen_at',
    ];

    protected function casts(): array
    {
        return ['seen_at' => 'datetime'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
