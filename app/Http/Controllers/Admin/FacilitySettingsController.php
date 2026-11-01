<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FacilitySettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.facility.edit', [
            'facility' => $request->user()->facility,
            'kinds' => RegisteredUserController::FACILITY_KINDS,
            'hasBills' => Bill::query()->exists(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $facility = $request->user()->facility;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'kind' => ['required', Rule::in(array_keys(RegisteredUserController::FACILITY_KINDS))],
            'district' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'moh_code' => ['nullable', 'string', 'max:30'],
            'currency' => ['required', Rule::in(array_keys(Money::CURRENCIES))],
        ]);

        $facility->update($data);

        return back()->with('status', 'Facility settings saved.');
    }
}
