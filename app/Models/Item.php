<?php

namespace App\Models;

use App\Enums\BillingType;
use App\Models\Concerns\BelongsToFacility;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use BelongsToFacility, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'sku',
        'name',
        'category',
        'billing_type',
        'unit',
        'cash_price',
        'reorder_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'billing_type' => BillingType::class,
            'cash_price' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }
}
