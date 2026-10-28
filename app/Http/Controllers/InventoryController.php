<?php

namespace App\Http\Controllers;

use App\Enums\BillingType;
use App\Enums\StockReason;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\StockAdjustment;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Services\ItemCatalogue;
use App\Services\ProcurementService;
use App\Services\StockReport;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct(
        private ProcurementService $procurement,
        private StockReport $reports,
    ) {}

    public function index(): View
    {
        $locations = StockLocation::query()->with(['batches.item'])->orderBy('name')->get();

        return view('inventory.index', [
            'locations' => $locations,
            'suggestions' => $locations->where('kind', '!=', 'main')->where('is_active', true)
                ->mapWithKeys(fn (StockLocation $location): array => [$location->id => $this->reports->restockSuggestions($location)]),
            'items' => Item::query()->orderBy('name')->get(),
            'billingTypes' => BillingType::cases(),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'orders' => PurchaseOrder::query()->with(['supplier', 'lines.item'])->latest()->limit(15)->get(),
            'requisitions' => Requisition::query()->with(['location', 'lines.item'])->orderByRaw("CASE WHEN status IN ('submitted', 'partial') THEN 0 ELSE 1 END")->latest()->limit(15)->get(),
            'adjustments' => StockAdjustment::query()->with(['item', 'location'])->latest()->limit(15)->get(),
        ]);
    }

    public function storeItem(Request $request, ItemCatalogue $catalogue): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:30'],
            'category' => ['required', Rule::in(['medicine', 'consumable', 'reagent'])],
            'billing_type' => ['required', Rule::enum(BillingType::class)],
            'cash_price' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:30'],
            'opening_quantity' => ['nullable', 'integer', 'min:1'],
            'stock_location_id' => ['required_with:opening_quantity', 'nullable', Rule::exists('stock_locations', 'id')->where('facility_id', $facilityId)],
            'batch_number' => ['nullable', 'string', 'max:40'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
        ]);

        $item = DB::transaction(function () use ($catalogue, $facilityId, $data): Item {
            $item = $catalogue->create($facilityId, $data);

            if (! empty($data['opening_quantity'])) {
                $catalogue->addStock(
                    StockLocation::query()->findOrFail($data['stock_location_id']),
                    $item,
                    (int) $data['opening_quantity'],
                    StockReason::Opening,
                    $data['batch_number'] ?? null,
                    isset($data['expiry_date']) ? Carbon::parse($data['expiry_date']) : null,
                    (int) ($data['unit_cost'] ?? 0),
                );
            }

            return $item;
        });

        return back()->with('status', empty($data['opening_quantity'])
            ? "{$item->name} added. Add stock for it when it arrives."
            : "{$item->name} added with {$data['opening_quantity']} in stock.");
    }

    public function addStock(Request $request, ItemCatalogue $catalogue): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'item_id' => ['required', Rule::exists('items', 'id')->where('facility_id', $facilityId)],
            'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where('facility_id', $facilityId)],
            'reason' => ['required', Rule::in([StockReason::Opening->value, StockReason::Purchase->value, StockReason::Donation->value])],
            'quantity' => ['required', 'integer', 'min:1'],
            'batch_number' => ['nullable', 'string', 'max:40'],
            'expiry_date' => ['nullable', 'date'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $item = Item::query()->findOrFail($data['item_id']);
        $location = StockLocation::query()->findOrFail($data['stock_location_id']);

        $catalogue->addStock(
            $location,
            $item,
            (int) $data['quantity'],
            StockReason::from($data['reason']),
            $data['batch_number'] ?? null,
            isset($data['expiry_date']) ? Carbon::parse($data['expiry_date']) : null,
            (int) ($data['unit_cost'] ?? 0),
            $data['notes'] ?? null,
        );

        return back()->with('status', "{$data['quantity']} {$item->unit} of {$item->name} added to {$location->name}.");
    }

    public function importItems(Request $request, ItemCatalogue $catalogue): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.mimes' => 'Upload a CSV file. In Excel, use File › Save As › CSV.',
        ]);

        $result = $catalogue->import($request->user()->facility_id, $request->file('file'));

        return back()->with('status', "Import finished: {$result['created']} new items, {$result['stocked']} stock lines added.");
    }

    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ItemCatalogue::IMPORT_COLUMNS);
            fputcsv($handle, ['Paracetamol 500mg', 'tablet', 'medicine', 'billable_medicine', '500', '400', 'PARA500', 'Main store', '2000', 'JMS-P25-001', now()->addYear()->toDateString(), '200']);
            fputcsv($handle, ['Examination gloves', 'pair', 'consumable', 'non_billable', '0', '80', '', 'Main store', '500', '', '', '400']);
            fclose($handle);
        }, 'items-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function loadStarterList(Request $request, ItemCatalogue $catalogue): RedirectResponse
    {
        $added = $catalogue->loadStarterList($request->user()->facility_id);

        return back()->with('status', $added > 0
            ? "{$added} common items added. Check the prices, then add stock."
            : 'Every item on the starter list is already here.');
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
            'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where('facility_id', $facilityId)->where('is_active', true)],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'distinct', Rule::exists('items', 'id')->where('facility_id', $facilityId)],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'lines.required' => 'Add at least one item to the requisition.',
            'lines.*.item_id.distinct' => 'Each item can only appear once on a requisition.',
        ]);

        $location = StockLocation::query()->findOrFail($data['stock_location_id']);

        if ($location->kind === 'main') {
            throw ValidationException::withMessages([
                'stock_location_id' => 'The main store restocks through purchase orders, not requisitions.',
            ]);
        }

        $requisition = $this->procurement->requestStock(
            $location,
            $request->user(),
            array_map(fn (array $line): array => [
                'item_id' => (int) $line['item_id'],
                'quantity' => (int) $line['quantity'],
            ], array_values($data['lines'])),
            $data['notes'] ?? null,
        );

        return back()->with('status', "Requisition {$requisition->number} submitted to the main store.");
    }

    public function issue(Requisition $requisition, Request $request): RedirectResponse
    {
        $this->procurement->issueRequisition($requisition, $request->user());

        $message = $requisition->fresh()->status === 'partial'
            ? 'Part of the requisition was issued. The main store did not have enough for the rest. Issue it again once stock arrives.'
            : 'Requisition issued. No patient was billed.';

        return back()->with('status', $message);
    }

    public function returnStock(Request $request): RedirectResponse
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where('facility_id', $facilityId)],
            'item_id' => ['required', Rule::exists('items', 'id')->where('facility_id', $facilityId)],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string', 'max:255'],
        ], [
            'notes.required' => 'Say why the stock is going back, for example "overstocked" or "near expiry".',
        ]);

        $this->procurement->returnToMainStore(
            StockLocation::query()->findOrFail($data['stock_location_id']),
            Item::query()->findOrFail($data['item_id']),
            (int) $data['quantity'],
            $data['notes'],
        );

        return back()->with('status', 'Stock returned to the main store.');
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
