<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PrescriptionItem;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Services\DispensingService;
use App\Services\StockReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PharmacyController extends Controller
{
    public function index(StockReport $reports): View
    {
        $store = StockLocation::query()->where('kind', 'pharmacy')->first();

        $shelf = StockBatch::query()
            ->with(['item', 'location'])
            ->where('quantity', '>', 0)
            ->whereHas('location', fn ($query) => $query->where('kind', 'pharmacy'))
            ->get()
            ->sortBy([
                ['item.name', 'asc'],
                ['expiry_date', 'asc'],
            ])
            ->values();

        return view('pharmacy.index', [
            'shelf' => $shelf,
            'onHand' => $shelf->groupBy('item_id')->map(fn ($rows) => (int) $rows->sum('quantity')),
            'items' => PrescriptionItem::query()
                ->with(['item', 'prescription.visit.patient', 'dispensations'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($query): void {
                    $query->where('status', 'pending')
                        ->orWhereHas('dispensations', fn ($dispensations) => $dispensations->whereNull('collected_at'));
                })
                ->latest()
                ->get(),
            'store' => $store,
            'suggestions' => $store ? $reports->restockSuggestions($store) : collect(),
            'stockItems' => Item::query()->where('is_active', true)->orderBy('name')->get(),
            'substitutes' => Item::query()->where('billing_type', 'billable_medicine')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function dispense(Request $request, PrescriptionItem $prescriptionItem, DispensingService $dispensing): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'substitute_item_id' => ['nullable', Rule::exists('items', 'id')->where('facility_id', $request->user()->facility_id)],
        ]);

        $dispensing->dispense(
            $prescriptionItem,
            $request->user(),
            (int) $data['quantity'],
            $data['substitute_item_id'] ?? null,
        );

        return back()->with('status', 'Dispensed and charged to the visit.');
    }

    public function cancel(PrescriptionItem $prescriptionItem, DispensingService $dispensing): RedirectResponse
    {
        $dispensing->cancel($prescriptionItem);

        return back()->with('status', 'Prescription line cancelled.');
    }
}
