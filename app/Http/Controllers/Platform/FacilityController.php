<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Role;
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
    public function index(): View
    {
        return view('platform.facilities.index', [
            'facilities' => Facility::query()
                ->withCount([
                    'patients' => fn ($query) => $query->withoutGlobalScope('facility'),
                    'users as admins_count' => fn ($query) => $query->where('role', Role::Administrator),
                ])
                ->orderBy('name')
                ->get(),
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

        return back()->with('status', 'Facility updated.');
    }
}
