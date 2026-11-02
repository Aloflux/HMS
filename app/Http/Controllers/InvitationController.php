<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetFacilityContext;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Accepting an invitation to work at a facility. Everyone chooses a password for this facility here;
 * someone new to Tunzuri also gives their name.
 */
class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $membership = $this->pending($token);

        if ($membership === null) {
            $expired = Membership::findByInvitationToken($token);

            return view('invitations.show', ['state' => 'invalid', 'token' => $token, 'facility' => $expired?->invitationHasExpired() ? $expired->facility : null]);
        }

        return view('invitations.show', [
            'membership' => $membership,
            'token' => $token,
            'state' => match (true) {
                ! $this->allowed($membership) => 'blocked',
                $this->isNewPerson($membership) => 'new',
                default => 'existing',
            },
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $membership = $this->pending($token);
        abort_if($membership === null, 404);

        $person = $membership->user;
        $isNewPerson = $this->isNewPerson($membership);

        if (! $this->allowed($membership)) {
            return redirect()->route('invitations.show', $token);
        }

        $data = $request->validate([
            'name' => [$isNewPerson ? 'required' : 'exclude', 'string', 'max:150'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($isNewPerson) {
            $person->forceFill([
                'name' => $data['name'],
                'email_verified_at' => $person->email_verified_at ?? now(),
            ])->save();
        }

        $membership->forceFill([
            'password' => $data['password'],
            'accepted_at' => now(),
            'invitation_token' => null,
        ])->save();

        Auth::login($person);
        $request->session()->regenerate();
        SetFacilityContext::enter($request, $membership);

        return redirect()->route('dashboard')->with('status', "Welcome to {$membership->facility->name}.");
    }

    /**
     * Administrators belong to one facility only. Only the invitee learns why an invitation cannot be taken up;
     * the inviting administrator still sees an ordinary pending invitation.
     */
    private function allowed(Membership $membership): bool
    {
        return $membership->user->canHoldRoleAt($membership->facility_id, $membership->role);
    }

    /**
     * Someone who has never joined a facility, so their name is still the one the administrator typed.
     */
    private function isNewPerson(Membership $membership): bool
    {
        return ! $membership->user->is_platform_admin
            && ! $membership->user->memberships()->whereNotNull('accepted_at')->exists();
    }

    private function pending(string $token): ?Membership
    {
        $membership = Membership::findByInvitationToken($token);

        $usable = $membership !== null
            && ! $membership->invitationHasExpired()
            && $membership->facility->is_active
            && $membership->user->is_active
            && ! $membership->user->is_platform_admin;

        return $usable ? $membership : null;
    }
}
