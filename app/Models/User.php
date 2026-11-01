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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'is_platform_admin',
        'phone',
        'is_active',
        'last_login_at',
    ];

    /**
     * The membership this person is working in for the current request.
     */
    private ?Membership $activeMembership = null;

    private bool $activeMembershipResolved = false;

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
            'is_platform_admin' => 'boolean',
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

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * People with an accepted membership at a facility, whether or not it is switched on.
     *
     * @param  Builder<User>  $query
     */
    public function scopeAtFacility(Builder $query, int $facilityId): void
    {
        $query->whereHas('memberships', fn (Builder $membership) => $membership->where('facility_id', $facilityId)->whereNotNull('accepted_at'));
    }

    /**
     * Work in this membership for the rest of the request.
     */
    public function useMembership(?Membership $membership): void
    {
        $this->activeMembership = $membership;
        $this->activeMembershipResolved = true;
    }

    /**
     * The membership in use. Unless one was chosen, a person with a single usable membership works in it.
     * Finding none is not remembered, so a membership created later is picked up.
     */
    public function activeMembership(): ?Membership
    {
        if (! $this->activeMembershipResolved) {
            $usable = $this->usableMemberships();

            if ($usable->count() === 1) {
                $this->useMembership($usable->first());
            }
        }

        return $this->activeMembership;
    }

    public function refresh(): static
    {
        $this->activeMembership = null;
        $this->activeMembershipResolved = false;

        return parent::refresh();
    }

    /**
     * @return Collection<int, Membership>
     */
    public function usableMemberships(): Collection
    {
        if ($this->is_platform_admin) {
            return new Collection;
        }

        return $this->memberships()
            ->usable()
            ->with(['facility', 'department'])
            ->get()
            ->sortBy(fn (Membership $membership): string => mb_strtolower($membership->facility->name))
            ->values();
    }

    /**
     * The facility of the membership in use.
     */
    protected function facilityId(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->activeMembership()?->facility_id);
    }

    protected function facility(): Attribute
    {
        return Attribute::get(fn (): ?Facility => $this->activeMembership()?->facility);
    }

    /**
     * Platform operators hold their role on the account; everyone else holds one per facility.
     */
    protected function role(): Attribute
    {
        return Attribute::get(fn (): ?Role => $this->is_platform_admin ? Role::PlatformAdmin : $this->activeMembership()?->role);
    }

    protected function permissions(): Attribute
    {
        return Attribute::get(fn (): ?array => $this->activeMembership()?->permissions);
    }

    protected function departmentId(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->activeMembership()?->department_id);
    }

    protected function department(): Attribute
    {
        return Attribute::get(fn (): ?Department => $this->activeMembership()?->department);
    }

    public function permits(string $permission): bool
    {
        if ($this->role === Role::PlatformAdmin) {
            return $permission === 'platform.manage';
        }

        if ($permission === 'platform.manage' || $this->role === null) {
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
        return $this->role === Role::PlatformAdmin ? $this->role->covers() : ($this->activeMembership()?->accessSummary() ?? 'No access');
    }

    /**
     * The work areas this person can open where they are working, without their department.
     */
    public function accessAreas(): string
    {
        return $this->role === Role::PlatformAdmin ? $this->role->covers() : ($this->activeMembership()?->accessAreas() ?? 'No access');
    }

    /**
     * @return list<string>
     */
    public function selectedAreaKeys(): array
    {
        return $this->activeMembership()?->selectedAreaKeys() ?? [];
    }

    public function homeRoute(): string
    {
        return $this->role === Role::PlatformAdmin
            ? 'platform.dashboard'
            : 'dashboard';
    }
}
