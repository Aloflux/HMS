<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(Request $request): View
    {
        $facilityCode = (string) $request->cookie(AuthenticatedSessionController::FACILITY_COOKIE, '');

        return view('auth.forgot-password', [
            'facilityCode' => $facilityCode,
            'facility' => AuthenticatedSessionController::facilityFor($facilityCode),
        ]);
    }

    /**
     * Send a reset link for one facility's password, or, with no code, a platform operator's.
     * The reply is the same whether or not the details match anyone.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'facility_code' => ['nullable', 'string', 'max:12'],
            'email' => ['required', 'email'],
        ]);

        $code = trim((string) ($data['facility_code'] ?? ''));
        $email = mb_strtolower(trim($data['email']));

        if ($code === '') {
            if (User::query()->where('email', $email)->where('is_platform_admin', true)->exists()) {
                Password::sendResetLink(['email' => $email]);
            } else {
                Membership::findAdministratorForSignIn($email)?->sendPasswordReset();
            }
        } else {
            Membership::findForSignIn($code, $email)?->sendPasswordReset();
        }

        return back()->withInput($request->only('facility_code', 'email'))
            ->with('status', 'If those details match an account, we have emailed a link to choose a new password.');
    }
}
