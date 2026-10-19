<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'name',
        'code',
        'kind',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stockLocations(): HasMany
    {
        return $this->hasMany(StockLocation::class);
    }
}
