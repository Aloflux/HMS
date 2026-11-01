<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'currency',
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

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * People who work here, through their memberships.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot(['id', 'role', 'permissions', 'department_id', 'is_active', 'last_login_at', 'accepted_at'])
            ->withTimestamps();
    }

    /**
     * The first administrator, who owns a self-registered facility.
     */
    public function firstAdministrator(): ?User
    {
        return $this->users()->wherePivot('role', Role::Administrator)->orderBy('memberships.id')->first();
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}
