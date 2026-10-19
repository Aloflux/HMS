<?php

namespace App\Models;

use App\Enums\BillingType;
use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WardConsumption extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'admission_id', 'stock_location_id', 'item_id', 'quantity', 'billing_type', 'bill_line_id', 'department_id', 'recorded_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['billing_type' => BillingType::class];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }
}
