<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetFacilityContext;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * People who work at more than one facility choose which one they are working in.
 */
class FacilityChoiceController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->is_platform_admin) {
            return redirect()->route($user->homeRoute());
        }

        return view('facilities.choose', [
            'memberships' => $user->usableMemberships(),
            'current' => $request->session()->get(SetFacilityContext::SESSION_KEY),
        ]);
    }

    public function store(Request $request, Membership $membership): RedirectResponse
    {
        $user = $request->user();

        abort_unless($membership->user_id === $user->id && $membership->isUsable(), 404);

        SetFacilityContext::enter($request, $membership);

        return redirect()->route('dashboard')->with('status', "You are now working at {$membership->facility->name}.");
    }
}
