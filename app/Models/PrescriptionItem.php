<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrescriptionItem extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'prescription_id', 'item_id', 'dose', 'frequency', 'duration_days', 'quantity', 'status', 'substituted_item_id',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function substitute(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'substituted_item_id');
    }

    public function dispensations(): HasMany
    {
        return $this->hasMany(Dispensation::class);
    }

    public function remainingQuantity(): int
    {
        return max(0, $this->quantity - (int) $this->dispensations()->sum('quantity'));
    }
}
