<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\StockAdjustment;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Services\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private ProcurementService $procurement) {}

    public function index(): View
    {
        return view('inventory.index', [
            'locations' => StockLocation::query()->with(['batches.item'])->orderBy('name')->get(),
            'items' => Item::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'orders' => PurchaseOrder::query()->with(['supplier', 'lines.item'])->latest()->limit(15)->get(),
            'requisitions' => Requisition::query()->with(['location', 'lines.item'])->latest()->limit(15)->get(),
            'adjustments' => StockAdjustment::query()->with(['item', 'location'])->latest()->limit(15)->get(),
        ]);
    }

    public function order(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('facility_id', $facilityId)],
            'supplier_name' => ['required_without:supplier_id', 'nullable', 'string', 'max:150'],
            'item_id' => ['required', Rule::exists('items', 'id')->where('facility_id', $facilityId)],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $supplier = isset($data['supplier_id'])
            ? Supplier::query()->findOrFail($data['supplier_id'])
            : Supplier::query()->create([
                'facility_id' => $facilityId,
                'name' => $data['supplier_name'],
                'is_active' => true,
            ]);
        $this->procurement->order($supplier, $request->user(), [[
            'item_id' => (int) $data['item_id'],
            'quantity' => (int) $data['quantity'],
            'unit_cost' => (int) $data['unit_cost'],
        ]], $data['notes'] ?? null);

        return back()->with('status', 'Purchase order sent.');
    }

    public function receive(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'purchase_order_line_id' => ['required', 'integer'],
            'batch_number' => ['required', 'string', 'max:40'],
            'expiry_date' => ['nullable', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->procurement->receive($order, (int) $data['purchase_order_line_id'], $request->user(), $data);

        return back()->with('status', 'Goods received into the main store.');
    }

    public function requisition(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where('facility_id', $facilityId)],
            'item_id' => ['required', Rule::exists('items', 'id')->where('facility_id', $facilityId)],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $location = StockLocation::query()->findOrFail($data['stock_location_id']);
        $this->procurement->requestStock($location, $request->user(), [[
            'item_id' => (int) $data['item_id'],
            'quantity' => (int) $data['quantity'],
        ]]);

        return back()->with('status', 'Requisition submitted.');
    }

    public function issue(Requisition $requisition, Request $request): RedirectResponse
    {
        $this->procurement->issueRequisition($requisition, $request->user());

        return back()->with('status', 'Requisition issued. No patient was billed.');
    }

    public function adjust(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where('facility_id', $facilityId)],
            'item_id' => ['required', Rule::exists('items', 'id')->where('facility_id', $facilityId)],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reason_code' => ['required', Rule::in(['expiry', 'damage', 'loss', 'count'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->procurement->requestAdjustment(
            StockLocation::query()->findOrFail($data['stock_location_id']),
            Item::query()->findOrFail($data['item_id']),
            null,
            (int) $data['quantity'],
            $data['reason_code'],
            $request->user(),
            $data['notes'] ?? null,
        );

        return back()->with('status', 'Adjustment is waiting for approval.');
    }

    public function approve(StockAdjustment $adjustment, Request $request): RedirectResponse
    {
        $this->procurement->approveAdjustment($adjustment, $request->user());

        return back()->with('status', 'Adjustment posted to the stock ledger.');
    }
}
