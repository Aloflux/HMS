<?php

namespace App\Models;

use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'kind',
        'country',
        'district',
        'address',
        'phone',
        'email',
        'bed_capacity',
        'moh_code',
        'is_active',
        'onboarded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'bed_capacity' => 'integer',
            'onboarded_at' => 'datetime',
        ];
    }

    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null;
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}
