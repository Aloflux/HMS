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

    /**
     * Routes a person with several facilities can open before choosing one.
     *
     * @var list<string>
     */
    private const BEFORE_CHOOSING = ['facilities.choose', 'facilities.switch', 'logout', 'verification.*', 'invitations.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            return $this->signOut($request, 'This account is not active.');
        }

        if ($user && ! $user->is_platform_admin) {
            $usable = $user->usableMemberships();
            $membership = $usable->firstWhere('id', $request->session()->get(self::SESSION_KEY))
                ?? ($usable->count() === 1 ? $usable->first() : null);

            if ($usable->isEmpty() && ! $request->routeIs('logout', 'invitations.*')) {
                return $this->signOut($request, 'You do not have access to an active facility.');
            }

            if ($membership !== null && $request->session()->get(self::SESSION_KEY) !== $membership->id) {
                self::enter($request, $membership);
            }

            $user->useMembership($membership);

            if ($membership === null && $usable->isNotEmpty() && ! $request->routeIs(...self::BEFORE_CHOOSING)) {
                return redirect()->route('facilities.choose');
            }
        }

        FacilityContext::set(
            $user?->facility_id,
            $user !== null && $user->is_platform_admin,
        );

        view()->share('currentFacility', $user?->facility);
        view()->share('canSwitchFacility', isset($usable) && $usable->count() > 1);
        view()->share('navigation', Navigation::sections($user));

        return $next($request);
    }

    public function terminate(): void
    {
        FacilityContext::forget();
    }

    /**
     * Start working in a membership for the rest of the session, recording the sign-in at that facility only.
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
