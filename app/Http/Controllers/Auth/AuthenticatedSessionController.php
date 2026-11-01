<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $samples = $this->sampleAccounts();

        return view('auth.login', [
            'demoAccounts' => $samples,
            'demoPassword' => $samples === [] ? null : config('hms.demo_password'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route($request->user()->homeRoute(), absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Active accounts in this database that still sign in with the shared development password.
     *
     * @return list<array{facility: string, name: string, role: string, email: string}>
     */
    private function sampleAccounts(): array
    {
        if (! app()->environment('local', 'development')) {
            return [];
        }

        $password = (string) config('hms.demo_password');

        if ($password === '') {
            return [];
        }

        return User::query()
            ->where('is_active', true)
            ->whereNotNull('password')
            ->where(function (Builder $query): void {
                $query->where('is_platform_admin', true)
                    ->orWhereHas('memberships', fn (Builder $membership) => $membership->usable());
            })
            ->with(['memberships' => fn ($membership) => $membership->usable()->with('facility:id,name')])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => Hash::check($password, $user->password))
            ->flatMap(fn (User $user): array => $user->is_platform_admin
                ? [['facility' => 'Tunzuri platform', 'name' => $user->name, 'role' => Role::PlatformAdmin->label(), 'email' => $user->email]]
                : $user->memberships->map(fn (Membership $membership): array => [
                    'facility' => $membership->facility->name,
                    'name' => $user->name,
                    'role' => $membership->role->label(),
                    'email' => $user->email,
                ])->all())
            ->sortBy(fn (array $account): string => mb_strtolower($account['facility'].' '.$account['name']))
            ->values()
            ->all();
    }
}
