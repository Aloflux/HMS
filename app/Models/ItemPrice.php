<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPrice extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'item_id',
        'insurance_scheme_id',
        'amount',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
