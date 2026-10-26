<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Department;
use App\Models\StockLocation;
use App\Models\Ward;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WardController extends Controller
{
    /**
     * Department kinds that may own a ward.
     *
     * @var list<string>
     */
    private const WARD_KINDS = ['ward', 'maternity', 'emergency', 'theatre'];

    public function index(): View
    {
        return view('admin.wards.index', [
            'wards' => Ward::query()
                ->with(['beds' => fn ($query) => $query->withCount('admissions')->orderBy('id')])
                ->orderBy('name')
                ->get(),
            'departments' => Department::query()->whereIn('kind', self::WARD_KINDS)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:12', 'alpha_dash', Rule::unique('wards', 'code')->where('facility_id', $facilityId)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('facility_id', $facilityId)->whereIn('kind', self::WARD_KINDS)],
            'beds' => ['required', 'integer', 'min:0', 'max:200'],
            'create_store' => ['boolean'],
        ]);

        DB::transaction(function () use ($data, $facilityId, $request): void {
            $ward = Ward::query()->create([
                'facility_id' => $facilityId,
                'department_id' => $data['department_id'] ?? null,
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
            ]);

            $this->addBeds($ward, (int) $data['beds']);

            $storeName = $ward->name.' store';

            if ($request->boolean('create_store') && ! StockLocation::query()->where('name', $storeName)->exists()) {
                StockLocation::query()->create([
                    'facility_id' => $facilityId,
                    'name' => $storeName,
                    'kind' => 'ward',
                    'department_id' => $ward->department_id,
                    'is_active' => true,
                ]);
            }
        });

        return back()->with('status', 'Ward added.');
    }

    public function update(Request $request, Ward $ward): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('facility_id', $facilityId)->whereIn('kind', self::WARD_KINDS)],
        ]);

        $ward->update([
            'name' => $data['name'],
            'department_id' => $data['department_id'] ?? null,
        ]);

        return back()->with('status', $ward->name.' updated.');
    }

    public function destroy(Ward $ward): RedirectResponse
    {
        if (Admission::query()->where('ward_id', $ward->id)->exists() || $ward->beds()->whereHas('admissions')->exists()) {
            throw ValidationException::withMessages([
                'ward' => $ward->name.' has admission history, so it cannot be removed. Close its beds instead.',
            ]);
        }

        $ward->delete();

        return back()->with('status', 'Ward removed.');
    }

    public function storeBeds(Request $request, Ward $ward): RedirectResponse
    {
        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $created = DB::transaction(fn (): int => $this->addBeds($ward, (int) $data['count']));

        return back()->with('status', $created.' '.str('bed')->plural($created).' added to '.$ward->name.'.');
    }

    public function updateBed(Request $request, Bed $bed): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['close', 'reopen'])],
        ]);

        if ($data['action'] === 'close') {
            if ($bed->status === 'occupied') {
                throw ValidationException::withMessages([
                    'bed' => $bed->code.' has a patient in it. Discharge or move the patient before closing the bed.',
                ]);
            }

            $bed->update(['status' => 'closed']);
            AuditLogger::log('updated', $bed, ['after' => ['status' => 'closed']]);

            return back()->with('status', $bed->code.' closed. It no longer takes admissions or counts toward capacity.');
        }

        if ($bed->status === 'closed') {
            $bed->update(['status' => 'available']);
            AuditLogger::log('updated', $bed, ['after' => ['status' => 'available']]);
        }

        return back()->with('status', $bed->code.' reopened.');
    }

    public function destroyBed(Bed $bed): RedirectResponse
    {
        if ($bed->status === 'occupied' || $bed->admissions()->exists()) {
            throw ValidationException::withMessages([
                'bed' => $bed->code.' has admission history, so it cannot be removed. Close it instead.',
            ]);
        }

        $bed->delete();
        AuditLogger::log('deleted', $bed);

        return back()->with('status', $bed->code.' removed.');
    }

    /**
     * Add beds numbered after the highest existing number in the ward, e.g. WF-5, WF-6.
     */
    private function addBeds(Ward $ward, int $count): int
    {
        $prefix = $ward->code.'-';
        $highest = $ward->beds()
            ->lockForUpdate()
            ->pluck('code')
            ->map(fn (string $code): int => str_starts_with($code, $prefix) && ctype_digit(substr($code, strlen($prefix))) ? (int) substr($code, strlen($prefix)) : 0)
            ->max() ?? 0;

        for ($number = $highest + 1; $number <= $highest + $count; $number++) {
            $ward->beds()->create([
                'facility_id' => $ward->facility_id,
                'code' => $prefix.$number,
                'status' => 'available',
            ]);
        }

        return $count;
    }
}
