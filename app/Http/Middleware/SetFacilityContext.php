<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Support\FacilityContext;
use App\Support\Navigation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetFacilityContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account is not active.',
            ]);
        }

        if ($user?->facility && ! $user->facility->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This facility is not active.',
            ]);
        }

        FacilityContext::set(
            $user?->facility_id,
            $user !== null && $user->role === Role::PlatformAdmin,
        );

        view()->share('currentFacility', $user?->facility);
        view()->share('navigation', Navigation::forUser($user));

        return $next($request);
    }

    public function terminate(): void
    {
        FacilityContext::forget();
    }
}
