<?php

namespace App\Http\Controllers;

use App\Enums\DosageForm;
use App\Enums\StockReason;
use App\Models\Department;
use App\Models\Item;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\StockReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockController extends Controller
{
    public function __construct(private StockReport $reports) {}

    public function index(Request $request): View
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'attention' => ['nullable', 'boolean'],
        ]);

        return view('stock.index', [
            'locations' => StockLocation::query()->where('is_active', true)->orderByRaw("CASE WHEN kind = 'main' THEN 0 WHEN kind = 'pharmacy' THEN 1 ELSE 2 END")->orderBy('name')->get(),
            'rows' => $this->reports->levels($data['search'] ?? null, (bool) ($data['attention'] ?? false)),
            'search' => $data['search'] ?? '',
            'attention' => (bool) ($data['attention'] ?? false),
        ]);
    }

    public function show(Request $request, Item $item): View
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'stock_location_id' => ['nullable', Rule::exists('stock_locations', 'id')->where('facility_id', $facilityId)],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $locationId = isset($data['stock_location_id']) ? (int) $data['stock_location_id'] : null;

        return view('stock.show', [
            'item' => $item,
            'locations' => StockLocation::query()->orderBy('name')->get(),
            'balances' => $item->batches()->with('location')->where('quantity', '>', 0)->orderBy('expiry_date')->get(),
            'movements' => $this->reports->card(
                $item,
                $locationId,
                isset($data['start']) ? Carbon::parse($data['start']) : null,
                isset($data['end']) ? Carbon::parse($data['end']) : null,
            ),
            'locationId' => $locationId,
            'forms' => DosageForm::cases(),
            'formDefaults' => collect(DosageForm::cases())->mapWithKeys(fn (DosageForm $form): array => [$form->value => $form->defaults()]),
            'start' => $data['start'] ?? null,
            'end' => $data['end'] ?? null,
        ]);
    }

    public function usage(Request $request): View
    {
        [$start, $end, $groupBy, $filters] = $this->usageFilters($request);
        $rows = $this->reports->usage($start, $end, $groupBy, $filters);
        $facilityId = $request->user()->facility_id;

        return view('stock.usage', [
            'rows' => $rows,
            'groups' => $rows->groupBy('group'),
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'groupBy' => $groupBy,
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->get(),
            'employees' => User::query()->where('facility_id', $facilityId)->orderBy('name')->get(),
            'items' => Item::query()->orderBy('name')->get(),
            'reasons' => [...StockReason::consumption(), StockReason::Adjustment, StockReason::Return],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$start, $end, $groupBy, $filters] = $this->usageFilters($request);
        $rows = $this->reports->usage($start, $end, $groupBy, $filters);

        return response()->streamDownload(function () use ($rows, $groupBy): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [$groupBy, 'item', 'unit', 'quantity', 'value_ugx']);

            foreach ($rows as $row) {
                fputcsv($handle, [$row['group'], $row['item'], $row['unit'], $row['quantity'], $row['value']]);
            }

            fclose($handle);
        }, 'stock-usage-'.$start->toDateString().'-to-'.$end->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: array{department_id: ?int, performed_by: ?int, item_id: ?int, reason: ?string}}
     */
    private function usageFilters(Request $request): array
    {
        $facilityId = $request->user()->facility_id;
        $data = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'group_by' => ['nullable', Rule::in(['department', 'employee', 'item'])],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('facility_id', $facilityId)],
            'performed_by' => ['nullable', Rule::exists('users', 'id')->where('facility_id', $facilityId)],
            'item_id' => ['nullable', Rule::exists('items', 'id')->where('facility_id', $facilityId)],
            'reason' => ['nullable', Rule::in([...array_map(fn (StockReason $reason): string => $reason->value, StockReason::consumption()), StockReason::Adjustment->value, StockReason::Return->value])],
        ]);

        return [
            isset($data['start']) ? Carbon::parse($data['start']) : now()->startOfMonth(),
            isset($data['end']) ? Carbon::parse($data['end']) : now()->endOfMonth(),
            $data['group_by'] ?? 'department',
            [
                'department_id' => isset($data['department_id']) ? (int) $data['department_id'] : null,
                'performed_by' => isset($data['performed_by']) ? (int) $data['performed_by'] : null,
                'item_id' => isset($data['item_id']) ? (int) $data['item_id'] : null,
                'reason' => $data['reason'] ?? null,
            ],
        ];
    }
}
