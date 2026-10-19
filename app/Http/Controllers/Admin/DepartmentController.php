<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20'],
            'kind' => ['required', 'string', 'max:30'],
        ]);

        Department::query()->create([
            ...$data,
            'facility_id' => $request->user()->facility_id,
            'is_active' => true,
        ]);

        return back()->with('status', 'Department added.');
    }
}
