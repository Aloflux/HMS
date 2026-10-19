<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceScheme extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'name',
        'kind',
        'cover_limit',
        'requires_preauthorisation',
        'contact_name',
        'contact_phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_preauthorisation' => 'boolean',
            'is_active' => 'boolean',
            'cover_limit' => 'integer',
        ];
    }

    public function servicePrices(): HasMany
    {
        return $this->hasMany(ServicePrice::class);
    }
}
