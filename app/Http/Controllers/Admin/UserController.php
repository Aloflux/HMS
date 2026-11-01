<?php

namespace App\Http\Controllers\Admin;

use App\Authorization\RolePermissions;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Membership;
use App\Models\User;
use App\Notifications\MembershipInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * An administrator manages who works at their facility. Whether an email already has a Tunzuri account
 * elsewhere is never revealed: adding anyone who is not already a member here always sends an invitation.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        return view('admin.users.index', [
            'memberships' => Membership::query()
                ->where('facility_id', $request->user()->facility_id)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->where('invited_name', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$search}%"))
                            ->orWhere(fn ($accepted) => $accepted->whereNotNull('accepted_at')->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")));
                    });
                })
                ->with(['user', 'department'])
                ->join('users', 'users.id', '=', 'memberships.user_id')
                ->orderByRaw('case when memberships.accepted_at is null then 0 else 1 end')
                ->orderByRaw('case when memberships.accepted_at is null then memberships.invited_name else users.name end')
                ->select('memberships.*')
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
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $identity = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
        ]);
        $assignment = $this->assignment($request);
        $facilityId = $request->user()->facility_id;

        $person = User::query()->where('email', $identity['email'])->first();
        $here = $person?->memberships()->where('facility_id', $facilityId)->first();

        if ($here !== null) {
            return back()->withInput()->withErrors(['email' => $here->isAccepted()
                ? "{$here->displayName()} is already a member of this facility."
                : "{$identity['email']} has already been invited here. Open their row to send the invitation again.",
            ]);
        }

        [$person, $membership, $token] = DB::transaction(function () use ($person, $identity, $assignment, $facilityId, $request): array {
            if ($person === null) {
                $person = new User(['name' => $identity['name'], 'email' => $identity['email'], 'is_active' => true]);
                $person->saveQuietly();
            }

            $membership = $person->memberships()->create([
                ...$assignment,
                'facility_id' => $facilityId,
                'is_active' => true,
                'invited_name' => $identity['name'],
            ]);

            return [$person, $membership, $membership->issueInvitation($request->user())];
        });

        $person->notify(new MembershipInvitation($membership, $token));

        return redirect()->route('admin.users.index')->with('status', "Invitation sent to {$identity['email']}.");
    }

    public function edit(Request $request, Membership $membership): View
    {
        $this->ensureHere($request, $membership);

        return view('admin.users.edit', [
            'membership' => $membership->load(['user', 'department']),
            ...$this->accessOptions(),
        ]);
    }

    public function update(Request $request, Membership $membership): RedirectResponse
    {
        $this->ensureHere($request, $membership);

        $assignment = $this->assignment($request);
        $status = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($membership->user_id === $request->user()->id && ($assignment['role'] !== Role::Administrator || ! $status['is_active'])) {
            return back()->with('error', 'You cannot remove your own full access or turn off your own account.');
        }

        $membership->update([
            ...$assignment,
            'is_active' => (bool) $status['is_active'],
        ]);

        return redirect()->route('admin.users.index')->with('status', "{$membership->displayName()} updated.");
    }

    /**
     * Email the person a link to choose a new password. The reply never depends on the outcome.
     */
    public function sendPasswordLink(Request $request, Membership $membership): RedirectResponse
    {
        $this->ensureHere($request, $membership);
        abort_unless($membership->isAccepted(), 404);

        Password::sendResetLink(['email' => $membership->user->email]);

        return back()->with('status', "Password reset link sent to {$membership->user->email}.");
    }

    public function resendInvitation(Request $request, Membership $membership): RedirectResponse
    {
        $this->ensureHere($request, $membership);
        abort_if($membership->isAccepted(), 404);

        $membership->user->notify(new MembershipInvitation($membership, $membership->issueInvitation($request->user())));

        return back()->with('status', "Invitation sent again to {$membership->user->email}.");
    }

    /**
     * Withdraw an invitation that has not been accepted.
     */
    public function cancelInvitation(Request $request, Membership $membership): RedirectResponse
    {
        $this->ensureHere($request, $membership);
        abort_if($membership->isAccepted(), 404);

        $person = $membership->user;
        $email = $person->email;

        DB::transaction(function () use ($membership, $person): void {
            $membership->delete();

            if ($person->password === null && ! $person->memberships()->exists()) {
                $person->deleteQuietly();
            }
        });

        return redirect()->route('admin.users.index')->with('status', "Invitation to {$email} withdrawn.");
    }

    private function ensureHere(Request $request, Membership $membership): void
    {
        abort_unless($membership->facility_id === $request->user()->facility_id, 404);
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
