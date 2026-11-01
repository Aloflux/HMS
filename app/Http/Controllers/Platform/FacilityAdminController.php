<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Platform operators manage who administers each facility, without seeing its patient data.
 */
class FacilityAdminController extends Controller
{
    public function index(Facility $facility): View
    {
        return view('platform.facilities.admins', [
            'facility' => $facility,
            'admins' => $facility->memberships()->where('role', Role::Administrator)->with('user')->get()->sortBy('user.name')->values(),
            'staffCount' => $facility->memberships()->where('role', '!=', Role::Administrator)->count(),
        ]);
    }

    /**
     * Operators may see every facility, so an existing account is linked directly instead of invited,
     * with a password for this facility that the operator sets.
     */
    public function store(Request $request, Facility $facility): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $existing = User::query()->where('email', $request->input('email'))->first();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($existing?->is_platform_admin) {
            return back()->withErrors(['email' => 'That email belongs to a platform administrator.'])->withInput();
        }

        $user = $existing ?? User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Membership::query()->updateOrCreate(
            ['facility_id' => $facility->id, 'user_id' => $user->id],
            ['role' => Role::Administrator, 'permissions' => null, 'password' => $data['password'], 'is_active' => true, 'accepted_at' => now(), 'invitation_token' => null],
        );

        return back()->with('status', $existing
            ? "{$user->name} already had a Tunzuri account. They can now administer {$facility->name} with code {$facility->code} and the password you set."
            : "{$user->name} can now administer {$facility->name} with code {$facility->code}.");
    }

    public function update(Request $request, Facility $facility, Membership $membership): RedirectResponse
    {
        abort_unless($membership->facility_id === $facility->id && $membership->role === Role::Administrator, 404);

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', Password::defaults()],
        ]);

        $deactivating = $membership->is_active && ! $data['is_active'];
        $otherActiveAdmins = $facility->memberships()
            ->where('role', Role::Administrator)
            ->where('is_active', true)
            ->whereNotNull('accepted_at')
            ->whereKeyNot($membership->id)
            ->exists();

        if ($deactivating && ! $otherActiveAdmins) {
            return back()->with('error', "{$facility->name} needs at least one active administrator. Add another before turning this one off.");
        }

        $membership->update(['is_active' => (bool) $data['is_active']]);

        if (filled($data['password'] ?? null)) {
            $membership->update(['password' => $data['password']]);
        }

        return back()->with('status', filled($data['password'] ?? null) ? "{$membership->user->name}'s password was reset." : "{$membership->user->name} updated.");
    }
}
