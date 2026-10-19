<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->where('facility_id', auth()->user()->facility_id)->with('department')->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'roles' => Role::facilityRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(array_map(fn (Role $role): string => $role->value, Role::facilityRoles()))],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('facility_id', $facilityId)],
        ]);

        User::query()->create([
            ...$data,
            'facility_id' => $facilityId,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return back()->with('status', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->facility_id === $request->user()->facility_id, 404);

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'role' => ['required', Rule::in(array_map(fn (Role $role): string => $role->value, Role::facilityRoles()))],
        ]);

        $user->update($data);

        return back()->with('status', 'User updated.');
    }
}
