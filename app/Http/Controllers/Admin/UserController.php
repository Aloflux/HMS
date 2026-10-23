<?php

namespace App\Http\Controllers\Admin;

use App\Authorization\RolePermissions;
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
        $departments = Department::query()->orderBy('name')->get();

        return view('admin.users.index', [
            'users' => User::query()->where('facility_id', auth()->user()->facility_id)->with('department')->orderBy('name')->get(),
            'departments' => $departments,
            'areas' => RolePermissions::areas(),
            'departmentAreas' => $departments->mapWithKeys(fn (Department $department): array => [
                $department->id => RolePermissions::areaKeysForDepartmentKind($department->kind),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $identity = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        User::query()->create([
            'name' => $identity['name'],
            'email' => $identity['email'],
            'password' => $identity['password'],
            'phone' => $identity['phone'] ?? null,
            'facility_id' => $request->user()->facility_id,
            'is_active' => true,
            'email_verified_at' => now(),
            ...$this->assignment($request),
        ]);

        return back()->with('status', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->facility_id === $request->user()->facility_id, 404);

        $assignment = $this->assignment($request);
        $active = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->is($request->user()) && $assignment['role'] !== Role::Administrator) {
            return back()->with('error', 'You cannot remove your own full access.');
        }

        $user->update([
            ...$assignment,
            'is_active' => $active['is_active'],
        ]);

        return back()->with('status', 'Access updated.');
    }

    /**
     * @return array{role: Role, permissions: list<string>|null, department_id: int|null}
     */
    private function assignment(Request $request): array
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'account' => ['required', Rule::in(['administrator', 'staff'])],
            'areas' => ['exclude_if:account,administrator', 'required', 'array', 'min:1'],
            'areas.*' => ['string', Rule::in(array_column(RolePermissions::areas(), 'key'))],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('facility_id', $facilityId)],
        ]);

        $administrator = $data['account'] === 'administrator';

        return [
            'role' => $administrator ? Role::Administrator : Role::Staff,
            'permissions' => $administrator ? null : RolePermissions::permissionsForAreas($data['areas']),
            'department_id' => $data['department_id'] ?? null,
        ];
    }
}
