<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'facility' => ['nullable', 'string', 'max:12'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (filled($request->input('facility'))) {
            return $this->resetFacilityPassword($request);
        }

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }

    /**
     * Change one facility's password from a link sent for it.
     */
    private function resetFacilityPassword(Request $request): RedirectResponse
    {
        $membership = Membership::findByPasswordResetToken((string) $request->input('token'));

        $matches = $membership !== null
            && mb_strtoupper(trim((string) $request->input('facility'))) === $membership->facility->code
            && mb_strtolower(trim((string) $request->input('email'))) === $membership->user->email;

        if (! $matches) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __(Password::INVALID_TOKEN)]);
        }

        $membership->forceFill([
            'password' => $request->input('password'),
            'password_reset_token' => null,
            'password_reset_sent_at' => null,
        ])->save();

        event(new PasswordReset($membership->user));

        return redirect()->route('login')
            ->withCookie(cookie(AuthenticatedSessionController::FACILITY_COOKIE, $membership->facility->code, 60 * 24 * 365))
            ->with('status', "Your password for {$membership->facility->name} has been changed. Sign in with facility code {$membership->facility->code}.");
    }
}
