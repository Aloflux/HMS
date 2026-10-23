<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Platform operators manage who administers each facility, without seeing its patient data.
 */
class FacilityAdminController extends Controller
{
    public function index(Facility $facility): View
    {
        return view('platform.facilities.admins', [
            'facility' => $facility,
            'admins' => $facility->users()->where('role', Role::Administrator)->orderBy('name')->get(),
            'staffCount' => $facility->users()->where('role', '!=', Role::Administrator)->count(),
        ]);
    }

    public function store(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
        ]);

        User::query()->create([
            ...$data,
            'facility_id' => $facility->id,
            'role' => Role::Administrator,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return back()->with('status', "{$data['name']} can now administer {$facility->name}.");
    }

    public function update(Request $request, Facility $facility, User $user): RedirectResponse
    {
        abort_unless($user->facility_id === $facility->id && $user->role === Role::Administrator, 404);

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', Password::defaults()],
        ]);

        $deactivating = $user->is_active && ! $data['is_active'];
        $otherActiveAdmins = $facility->users()
            ->where('role', Role::Administrator)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->exists();

        if ($deactivating && ! $otherActiveAdmins) {
            return back()->with('error', "{$facility->name} needs at least one active administrator. Add another before turning this one off.");
        }

        $user->update(array_filter([
            'is_active' => (bool) $data['is_active'],
            'password' => $data['password'] ?? null,
        ], fn (mixed $value): bool => $value !== null));

        return back()->with('status', filled($data['password'] ?? null) ? "{$user->name}'s password was reset." : "{$user->name} updated.");
    }
}
