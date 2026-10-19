<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'patient_id', 'admission_id', 'delivered_at', 'mode', 'outcome', 'baby_sex', 'birth_weight_kg', 'apgar', 'notes', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'birth_weight_kg' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
