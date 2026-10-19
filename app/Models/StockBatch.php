<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Database\Factories\StockBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    /** @use HasFactory<StockBatchFactory> */
    use BelongsToFacility, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'item_id',
        'stock_location_id',
        'batch_number',
        'expiry_date',
        'unit_cost',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'unit_cost' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }
}
