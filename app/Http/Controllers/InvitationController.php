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
 * Accepting an invitation to work at a facility. Someone new chooses their name and password here;
 * someone who already has a Tunzuri account signs in with it and accepts.
 */
class InvitationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $membership = $this->pending($token);

        if ($membership === null) {
            $expired = Membership::findByInvitationToken($token);

            return view('invitations.show', ['state' => 'invalid', 'token' => $token, 'facility' => $expired?->invitationHasExpired() ? $expired->facility : null]);
        }

        $isNewPerson = $membership->user->password === null;

        if (! $isNewPerson && $request->user() === null) {
            redirect()->setIntendedUrl($request->fullUrl());
        }

        return view('invitations.show', [
            'membership' => $membership,
            'token' => $token,
            'state' => match (true) {
                $isNewPerson => 'new',
                $request->user() === null => 'sign-in',
                $request->user()->id !== $membership->user_id => 'other-account',
                default => 'accept',
            },
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $membership = $this->pending($token);
        abort_if($membership === null, 404);

        $person = $membership->user;

        if ($person->password === null) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:150'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $person->forceFill([
                'name' => $data['name'],
                'password' => $data['password'],
                'email_verified_at' => $person->email_verified_at ?? now(),
            ])->save();

            Auth::login($person);
            $request->session()->regenerate();
        }

        abort_unless($request->user()?->id === $person->id, 403);

        $membership->forceFill(['accepted_at' => now(), 'invitation_token' => null])->save();
        SetFacilityContext::enter($request, $membership);

        return redirect()->route('dashboard')->with('status', "Welcome to {$membership->facility->name}.");
    }

    private function pending(string $token): ?Membership
    {
        $membership = Membership::findByInvitationToken($token);

        return $membership === null || $membership->invitationHasExpired() || ! $membership->facility->is_active ? null : $membership;
    }
}
