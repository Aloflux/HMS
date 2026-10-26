<?php

namespace App\Models;

use App\Authorization\RolePermissions;
use App\Enums\Role;
use App\Jobs\SendNotification;
use App\Models\Concerns\RecordsAudit;
use App\Notifications\VerifyEmailAddress;
use App\Support\MailPressure;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, RecordsAudit;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'facility_id',
        'role',
        'permissions',
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
            'permissions' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Send the branded verification email instead of the framework default.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailAddress);
    }

    /**
     * Deliver mail in the request until a burst is large enough to stall it.
     */
    public function notify($instance)
    {
        if (MailPressure::shouldQueue()) {
            SendNotification::dispatch($this, $instance);

            return;
        }

        MailPressure::record();

        Notification::sendNow($this, $instance);
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

        if ($this->role === Role::Administrator) {
            return true;
        }

        $allowed = is_array($this->permissions)
            ? $this->permissions
            : RolePermissions::for($this->role);

        return in_array('*', $allowed, true) || in_array($permission, $allowed, true);
    }

    public function accessSummary(): string
    {
        $access = $this->accessAreas();

        return $this->department?->name ? $this->department->name.' · '.$access : $access;
    }

    /**
     * The work areas this account can open, without its department.
     */
    public function accessAreas(): string
    {
        if ($this->role === Role::Administrator) {
            return 'Full access';
        }

        if ($this->role === Role::PlatformAdmin) {
            return $this->role->covers();
        }

        $keys = $this->selectedAreaKeys();
        $labels = array_column(array_filter(
            RolePermissions::areas(),
            fn (array $area): bool => in_array($area['key'], $keys, true),
        ), 'label');

        return $labels === [] ? 'No areas assigned' : implode(', ', $labels);
    }

    /**
     * @return list<string>
     */
    public function selectedAreaKeys(): array
    {
        if ($this->role === Role::Administrator || $this->role === Role::PlatformAdmin) {
            return [];
        }

        $permissions = is_array($this->permissions)
            ? $this->permissions
            : RolePermissions::for($this->role);

        return RolePermissions::areasMatching($permissions);
    }

    public function homeRoute(): string
    {
        return $this->role === Role::PlatformAdmin
            ? 'platform.dashboard'
            : 'dashboard';
    }
}
