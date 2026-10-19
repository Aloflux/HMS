<?php

namespace App\Models;

use App\Enums\Payer;
use App\Models\Concerns\BelongsToFacility;
use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use BelongsToFacility, RecordsAudit;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'visit_id', 'patient_id', 'status', 'currency', 'waived_amount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'waived_amount' => 'integer',
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

    public function lines(): HasMany
    {
        return $this->hasMany(BillLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function waivers(): HasMany
    {
        return $this->hasMany(Waiver::class);
    }

    public function cashBalance(): int
    {
        $charges = (int) $this->lines->where('waived', false)->where('payer', Payer::Cash)->sum('amount');
        $paid = (int) $this->payments->where('status', 'confirmed')->sum('amount');

        return max(0, $charges - (int) $this->waived_amount - $paid);
    }

    public function insuranceBalance(): int
    {
        return (int) $this->lines
            ->where('waived', false)
            ->whereIn('payer', [Payer::Insurance, Payer::Corporate])
            ->sum('amount');
    }
}
