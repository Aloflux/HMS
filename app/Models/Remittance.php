<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Remittance extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'claim_id', 'amount', 'reference', 'received_at', 'received_by',
    ];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}
