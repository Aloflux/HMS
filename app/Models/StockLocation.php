<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Database\Factories\StockLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLocation extends Model
{
    /** @use HasFactory<StockLocationFactory> */
    use BelongsToFacility, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'name',
        'kind',
        'department_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }
}
