<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Role;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\FacilityProvisioner;
use App\Support\FacilityContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['active', 'inactive', 'setup'], true) ? $request->query('status') : 'all';
        $sort = $request->query('sort') === 'newest' ? 'newest' : 'name';

        $facilities = Facility::query()
            ->withCount([
                'patients' => fn ($query) => $query->withoutGlobalScope('facility'),
                'users as admins_count' => fn ($query) => $query->where('role', Role::Administrator),
                'users as active_admins_count' => fn ($query) => $query->where('role', Role::Administrator)->where('is_active', true),
                'users as staff_count',
            ])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('code', "%{$search}%")
                ->orWhereLike('district', "%{$search}%")))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true)->whereNotNull('onboarded_at'))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($status === 'setup', fn ($query) => $query->whereNull('onboarded_at'))
            ->when($sort === 'newest', fn ($query) => $query->latest(), fn ($query) => $query->orderBy('name'))
            ->get();

        $all = Facility::query()->get(['id', 'is_active', 'onboarded_at']);

        return view('platform.facilities.index', [
            'facilities' => $facilities,
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'counts' => [
                'all' => $all->count(),
                'active' => $all->where('is_active', true)->whereNotNull('onboarded_at')->count(),
                'setup' => $all->whereNull('onboarded_at')->count(),
                'inactive' => $all->where('is_active', false)->count(),
            ],
            'kinds' => RegisteredUserController::FACILITY_KINDS,
            'openProvision' => $request->boolean('provision') || (bool) session('errors')?->hasAny(['name', 'code', 'kind', 'district', 'bed_capacity', 'admin_name', 'admin_email', 'admin_password']),
        ]);
    }

    public function store(Request $request, FacilityProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:12', 'unique:facilities,code'],
            'kind' => ['required', Rule::in(['hospital', 'clinic', 'faith_based'])],
            'district' => ['required', 'string', 'max:100'],
            'bed_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'admin_password' => ['required', Password::defaults()],
        ]);

        $hide = FacilityContext::shouldHideTenantData();
        $previous = FacilityContext::id();

        $provisioner->provision([
            'name' => $data['name'],
            'code' => $data['code'],
            'kind' => $data['kind'],
            'district' => $data['district'],
            'bed_capacity' => (int) $data['bed_capacity'],
        ], [
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => $data['admin_password'],
        ]);

        FacilityContext::set($previous, $hide);

        return back()->with('status', 'Facility provisioned with departments, stores, wards and an administrator.');
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $facility->update($data);

        return back()->with('status', $facility->name.($facility->is_active ? ' is active again.' : ' is now inactive. Its staff cannot sign in.'));
    }
}
