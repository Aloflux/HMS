<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\User;
use App\Services\FacilityProvisioner;
use App\Support\FacilityContext;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const FACILITY_KINDS = [
        'hospital' => 'Hospital',
        'clinic' => 'Clinic / health centre',
        'faith_based' => 'Faith-based facility',
    ];

    /**
     * Display the facility sign-up form.
     */
    public function create(): View
    {
        return view('auth.register', ['kinds' => self::FACILITY_KINDS]);
    }

    /**
     * Sign up a new facility and its owner, then ask the owner to verify their email.
     */
    public function store(Request $request, FacilityProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'facility_name' => ['required', 'string', 'max:150'],
            'facility_kind' => ['required', Rule::in(array_keys(self::FACILITY_KINDS))],
            'district' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $facility = $provisioner->provision([
            'name' => $data['facility_name'],
            'code' => $this->uniqueFacilityCode($data['facility_name']),
            'kind' => $data['facility_kind'],
            'district' => $data['district'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
        ], [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
        ], selfService: true);

        FacilityContext::forget();

        $owner = User::query()
            ->where('facility_id', $facility->id)
            ->where('role', Role::Administrator)
            ->firstOrFail();

        event(new Registered($owner));

        Auth::login($owner);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    /**
     * Build a short facility code from the name's initials, e.g. "St Mary's Hospital Lacor" becomes "SMHL-482".
     */
    private function uniqueFacilityCode(string $name): string
    {
        $initials = Str::of($name)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z ]/', '')
            ->explode(' ')
            ->filter()
            ->map(fn (string $word): string => $word[0])
            ->take(4)
            ->implode('') ?: 'FAC';

        do {
            $code = $initials.'-'.random_int(100, 999);
        } while (Facility::query()->where('code', $code)->exists());

        return $code;
    }
}
