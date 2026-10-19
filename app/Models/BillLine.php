<?php

namespace App\Models;

use App\Enums\Payer;
use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillLine extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'bill_id', 'description', 'source_type', 'source_id', 'quantity', 'unit_price', 'amount', 'payer', 'waived',
    ];

    protected function casts(): array
    {
        return [
            'payer' => Payer::class,
            'waived' => 'boolean',
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
