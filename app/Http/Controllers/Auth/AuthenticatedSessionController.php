<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SetFacilityContext;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Facility;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Cookie that remembers the facility code on this device.
     */
    public const FACILITY_COOKIE = 'facility_code';

    /**
     * Display the login view.
     */
    public function create(): View
    {
        $samples = $this->sampleAccounts();

        $facilityCode = (string) request()->cookie(self::FACILITY_COOKIE, '');

        return view('auth.login', [
            'facilityCode' => $facilityCode,
            'facility' => self::facilityFor($facilityCode),
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

        if ($request->membership !== null) {
            SetFacilityContext::enter($request, $request->membership);
        }

        $code = $request->membership?->facility->code;
        Cookie::queue($code !== null && $request->boolean('remember_facility', true)
            ? Cookie::make(self::FACILITY_COOKIE, $code, 60 * 24 * 365)
            : Cookie::forget(self::FACILITY_COOKIE));

        return redirect()->intended(route($request->user()->homeRoute(), absolute: false));
    }

    /**
     * Name the facility a code belongs to, so the person can see where they are signing in before giving a password.
     * Facility names are public; nothing about who works there is returned.
     */
    public function facility(Request $request): JsonResponse
    {
        $facility = self::facilityFor((string) $request->query('code'));

        return $facility === null
            ? response()->json(['found' => false], 404)
            : response()->json(['found' => true, ...$facility]);
    }

    /**
     * @return array{name: string, district: string|null}|null
     */
    public static function facilityFor(string $code): ?array
    {
        $code = mb_strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        $facility = Facility::query()->where('code', $code)->where('is_active', true)->first(['name', 'district']);

        return $facility === null ? null : ['name' => $facility->name, 'district' => $facility->district];
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
     * @return list<array{facility: string, code: string, name: string, role: string, email: string}>
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

        $operators = User::query()
            ->where('is_active', true)
            ->where('is_platform_admin', true)
            ->get()
            ->filter(fn (User $user): bool => Hash::check($password, (string) $user->password))
            ->map(fn (User $user): array => ['facility' => 'Tunzuri platform', 'code' => '', 'name' => $user->name, 'role' => Role::PlatformAdmin->label(), 'email' => $user->email]);

        $staff = Membership::query()
            ->usable()
            ->whereNotNull('password')
            ->whereHas('user', fn (Builder $user) => $user->where('is_active', true))
            ->with(['user', 'facility:id,name,code'])
            ->get()
            ->filter(fn (Membership $membership): bool => Hash::check($password, $membership->password))
            ->map(fn (Membership $membership): array => [
                'facility' => $membership->facility->name,
                'code' => $membership->facility->code,
                'name' => $membership->user->name,
                'role' => $membership->role->label(),
                'email' => $membership->user->email,
            ]);

        return $operators->concat($staff)
            ->sortBy(fn (array $account): string => mb_strtolower($account['facility'].' '.$account['name']))
            ->values()
            ->all();
    }
}
