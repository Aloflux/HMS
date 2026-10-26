<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFacilityOnboarded
{
    /**
     * Send the owner of a self-registered facility to the setup wizard until it is finished.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $facility = $request->user()?->facility;

        if ($facility !== null && ! $facility->isOnboarded()) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
