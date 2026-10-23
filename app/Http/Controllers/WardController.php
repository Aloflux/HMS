<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Item;
use App\Models\StockLocation;
use App\Models\Ward;
use App\Services\EncounterService;
use App\Services\WardStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WardController extends Controller
{
    public function index(): View
    {
        return view('wards.index', [
            'wards' => Ward::query()->with(['beds'])->orderBy('name')->get(),
            'admissions' => Admission::query()->with(['patient', 'bed.ward'])->where('status', 'admitted')->get(),
        ]);
    }

    public function show(Admission $admission): View
    {
        $admission->load([
            'patient',
            'bed.ward',
            'visit.bill.lines',
            'visit.bill.payments',
            'nursingNotes.recorder',
            'administrations.item',
            'consumptions.item',
        ]);

        return view('wards.show', [
            'admission' => $admission,
            'items' => Item::query()->where('is_active', true)->orderBy('name')->get(),
            'locations' => StockLocation::query()->where('kind', 'ward')->orderBy('name')->get(),
        ]);
    }

    public function note(Request $request, Admission $admission): RedirectResponse
    {
        $admission->ensureOpen();
        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);

        $admission->nursingNotes()->create([
            'facility_id' => $admission->facility_id,
            'note' => $data['note'],
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Nursing note saved.');
    }

    public function medication(Request $request, Admission $admission): RedirectResponse
    {
        $admission->ensureOpen();
        $data = $request->validate([
            'item_id' => ['required', Rule::exists('items', 'id')->where('facility_id', $request->user()->facility_id)],
            'dose' => ['required', 'string', 'max:60'],
            'status' => ['required', Rule::in(['due', 'given', 'missed', 'refused'])],
        ]);

        $admission->administrations()->create([
            'facility_id' => $admission->facility_id,
            'item_id' => $data['item_id'],
            'dose' => $data['dose'],
            'scheduled_at' => now(),
            'administered_at' => $data['status'] === 'given' ? now() : null,
            'administered_by' => $data['status'] === 'given' ? $request->user()->id : null,
            'status' => $data['status'],
        ]);

        return back()->with('status', 'Medication chart updated. Charting a dose does not move stock.');
    }

    public function issue(Request $request, Admission $admission, WardStockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', Rule::exists('items', 'id')->where('facility_id', $request->user()->facility_id)],
            'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where('facility_id', $request->user()->facility_id)],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $stock->issue(
            $admission,
            Item::query()->findOrFail($data['item_id']),
            StockLocation::query()->findOrFail($data['stock_location_id']),
            (int) $data['quantity'],
            $request->user(),
            $data['notes'] ?? null,
        );

        return back()->with('status', 'Ward stock issued.');
    }

    public function discharge(Request $request, Admission $admission, EncounterService $encounters): RedirectResponse
    {
        $data = $request->validate([
            'discharge_summary' => ['required', 'string', 'max:5000'],
            'discharge_outcome' => ['required', Rule::in(['recovered', 'referred', 'died', 'absconded'])],
        ]);

        $encounters->discharge($admission, $request->user(), $data['discharge_summary'], $data['discharge_outcome']);

        return redirect()->route('wards.index')->with('status', 'Patient discharged.');
    }

    public function cleanBed(Bed $bed): RedirectResponse
    {
        abort_unless($bed->status === 'cleaning', 422);
        $bed->update(['status' => 'available']);

        return back()->with('status', 'Bed is available.');
    }
}
