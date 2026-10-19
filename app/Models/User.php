<?php

namespace App\Models;

use App\Authorization\RolePermissions;
use App\Enums\Role;
use App\Models\Concerns\RecordsAudit;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, RecordsAudit;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'facility_id',
        'role',
        'phone',
        'department_id',
        'is_active',
        'last_login_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function permits(string $permission): bool
    {
        if ($this->role === Role::PlatformAdmin) {
            return $permission === 'platform.manage';
        }

        if ($permission === 'platform.manage') {
            return false;
        }

        $allowed = RolePermissions::for($this->role);

        return in_array('*', $allowed, true) || in_array($permission, $allowed, true);
    }

    public function homeRoute(): string
    {
        return $this->role === Role::PlatformAdmin
            ? 'platform.facilities.index'
            : 'dashboard';
    }
}
