<?php

namespace App\Http\Controllers\Admin;

use App\Authorization\RolePermissions;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        return view('admin.users.index', [
            'users' => User::query()
                ->where('facility_id', $request->user()->facility_id)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->with('department')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', $this->accessOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $identity = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = User::query()->create([
            'name' => $identity['name'],
            'email' => $identity['email'],
            'password' => $identity['password'],
            'phone' => $identity['phone'] ?? null,
            'facility_id' => $request->user()->facility_id,
            'is_active' => true,
            'email_verified_at' => now(),
            ...$this->assignment($request),
        ]);

        return redirect()->route('admin.users.index')->with('status', "{$user->name} added.");
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($user->facility_id === $request->user()->facility_id, 404);

        return view('admin.users.edit', [
            'user' => $user,
            ...$this->accessOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->facility_id === $request->user()->facility_id, 404);

        $assignment = $this->assignment($request);
        $details = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->is($request->user()) && ($assignment['role'] !== Role::Administrator || ! $details['is_active'])) {
            return back()->with('error', 'You cannot remove your own full access or turn off your own account.');
        }

        $user->update([
            ...$details,
            ...$assignment,
        ]);

        return redirect()->route('admin.users.index')->with('status', "{$user->name} updated.");
    }

    /**
     * @return array{departments: \Illuminate\Database\Eloquent\Collection<int, Department>, areas: list<array{key: string, label: string, detail: string, permissions: list<string>}>, departmentAreas: Collection<int, list<string>>}
     */
    private function accessOptions(): array
    {
        $departments = Department::query()->orderBy('name')->get();

        return [
            'departments' => $departments,
            'areas' => RolePermissions::areas(),
            'departmentAreas' => $departments->mapWithKeys(fn (Department $department): array => [
                $department->id => RolePermissions::areaKeysForDepartmentKind($department->kind),
            ]),
        ];
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
