<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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
            ->with('facility:id,name,is_active')
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('facility_id')
                    ->orWhereHas('facility', function (Builder $facility): void {
                        $facility->where('is_active', true);
                    });
            })
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => Hash::check($password, $user->password))
            ->sortBy(fn (User $user): string => mb_strtolower(($user->facility?->name ?? '').' '.$user->name))
            ->values()
            ->map(fn (User $user): array => [
                'facility' => $user->facility?->name ?? 'Tunzuri platform',
                'name' => $user->name,
                'role' => $user->role->label(),
                'email' => $user->email,
            ])
            ->all();
    }
}
