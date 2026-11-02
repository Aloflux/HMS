<?php

namespace App\Models;

use App\Authorization\RolePermissions;
use App\Enums\Role;
use App\Models\Concerns\RecordsAudit;
use App\Notifications\MembershipPasswordReset;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A person working at a facility: their role, access and status there.
 */
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory, RecordsAudit;

    /**
     * Days an invitation link stays valid.
     */
    public const INVITATION_DAYS = 7;

    /**
     * Minutes a password reset link stays valid.
     */
    public const PASSWORD_RESET_MINUTES = 60;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'user_id',
        'role',
        'permissions',
        'department_id',
        'password',
        'is_active',
        'last_login_at',
        'invited_name',
        'invited_by',
        'invitation_token',
        'invited_at',
        'accepted_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'invitation_token',
        'password_reset_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'permissions' => 'array',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'password_reset_sent_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Start a fresh invitation and return its token. Only a hash is stored, so the link cannot be rebuilt from the database.
     */
    public function issueInvitation(?User $inviter): string
    {
        $token = Str::random(48);

        $this->forceFill([
            'invitation_token' => hash('sha256', $token),
            'invited_by' => $inviter?->id,
            'invited_at' => now(),
        ])->save();

        return $token;
    }

    /**
     * Start a password reset for this facility only and return its token.
     */
    public function issuePasswordReset(): string
    {
        $token = Str::random(48);

        $this->forceFill([
            'password_reset_token' => hash('sha256', $token),
            'password_reset_sent_at' => now(),
        ])->saveQuietly();

        return $token;
    }

    /**
     * Email a link to reset this facility's password. A repeat within a minute is ignored.
     */
    public function sendPasswordReset(): void
    {
        if ($this->password_reset_sent_at?->gt(now()->subMinute())) {
            return;
        }

        $this->user->notify(new MembershipPasswordReset($this, $this->issuePasswordReset()));
    }

    public static function findByPasswordResetToken(string $token): ?self
    {
        $membership = self::query()->where('password_reset_token', hash('sha256', $token))->first();

        return $membership?->password_reset_sent_at?->gte(now()->subMinutes(self::PASSWORD_RESET_MINUTES)) ? $membership : null;
    }

    /**
     * The usable membership at the facility with this code for this email, if there is one.
     */
    public static function findForSignIn(string $facilityCode, string $email): ?self
    {
        return self::query()
            ->usable()
            ->whereHas('facility', fn (Builder $facility) => $facility->where('code', mb_strtoupper(trim($facilityCode))))
            ->whereHas('user', fn (Builder $user) => $user->where('email', mb_strtolower(trim($email)))->where('is_active', true))
            ->with(['user', 'facility'])
            ->first();
    }

    /**
     * The usable administrator membership for this email. Administrators belong to one facility only,
     * so they can sign in without a facility code.
     */
    public static function findAdministratorForSignIn(string $email): ?self
    {
        return self::query()
            ->usable()
            ->where('role', Role::Administrator)
            ->whereHas('user', fn (Builder $user) => $user->where('email', mb_strtolower(trim($email)))->where('is_active', true)->where('is_platform_admin', false))
            ->with(['user', 'facility'])
            ->first();
    }

    public static function findByInvitationToken(string $token): ?self
    {
        return self::query()
            ->where('invitation_token', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->first();
    }

    public function invitationHasExpired(): bool
    {
        return $this->invited_at === null || $this->invited_at->lt(now()->subDays(self::INVITATION_DAYS));
    }

    /**
     * The name to show: the person's own once they accept, the one the administrator typed until then.
     */
    public function displayName(): string
    {
        return $this->isAccepted() ? $this->user->name : ($this->invited_name ?? $this->user->email);
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Memberships a person can work in right now: accepted, switched on, at an active facility.
     *
     * @param  Builder<Membership>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->whereNotNull('accepted_at')
            ->where('is_active', true)
            ->whereHas('facility', fn (Builder $facility) => $facility->where('is_active', true));
    }

    public function accessSummary(): string
    {
        $access = $this->accessAreas();

        return $this->department?->name ? $this->department->name.' · '.$access : $access;
    }

    /**
     * The work areas this membership opens, without its department.
     */
    public function accessAreas(): string
    {
        if ($this->role === Role::Administrator) {
            return 'Full access';
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
        if ($this->role === Role::Administrator) {
            return [];
        }

        return RolePermissions::areasMatching(is_array($this->permissions) ? $this->permissions : RolePermissions::for($this->role));
    }

    public function isUsable(): bool
    {
        return $this->isAccepted() && $this->is_active && ($this->facility?->is_active ?? false);
    }
}
