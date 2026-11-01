<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use App\Models\User;
use App\Support\FacilityContext;
use App\Support\Navigation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetFacilityContext
{
    /**
     * Session key holding the membership the person chose to work in.
     */
    public const SESSION_KEY = 'membership_id';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            return $this->signOut($request, 'This account is not active.');
        }

        if ($user && ! $user->is_platform_admin) {
            $membership = $user->usableMemberships()->firstWhere('id', $request->session()->get(self::SESSION_KEY));

            if ($membership === null && ! $request->routeIs('logout', 'invitations.*')) {
                return $this->signOut($request, 'Sign in with your facility code to continue.');
            }

            $user->useMembership($membership);
        }

        FacilityContext::set(
            $user?->facility_id,
            $user !== null && $user->is_platform_admin,
        );

        view()->share('currentFacility', $user?->facility);
        view()->share('navigation', Navigation::sections($user));

        return $next($request);
    }

    public function terminate(): void
    {
        FacilityContext::forget();
    }

    /**
     * Start working in a membership for the rest of the session, recording the sign-in at that facility only.
     * Call it only after that facility's password has been checked. A session works in one facility; reaching
     * another means signing out and signing in with its code.
     */
    public static function enter(Request $request, Membership $membership): void
    {
        $request->session()->put(self::SESSION_KEY, $membership->id);
        $membership->forceFill(['last_login_at' => now()])->saveQuietly();

        /** @var User $user */
        $user = $request->user();
        $user->useMembership($membership);
    }

    private function signOut(Request $request, string $message): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
