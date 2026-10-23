<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const KINDS = [
        'opd' => 'Outpatient',
        'emergency' => 'Emergency',
        'laboratory' => 'Laboratory',
        'radiology' => 'Imaging',
        'pharmacy' => 'Pharmacy',
        'ward' => 'Ward',
        'maternity' => 'Maternity',
        'theatre' => 'Theatre',
        'stores' => 'Stores',
        'cashier' => 'Cashier',
        'administration' => 'Administration',
    ];

    public function index(): View
    {
        return view('admin.departments.index', [
            'departments' => Department::query()->withCount('users')->orderBy('name')->get(),
            'kinds' => self::KINDS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->where('facility_id', $facilityId)],
            'kind' => ['required', Rule::in(array_keys(self::KINDS))],
        ]);

        Department::query()->create([
            ...$data,
            'facility_id' => $facilityId,
            'is_active' => true,
        ]);

        return back()->with('status', 'Department added.');
    }
}
