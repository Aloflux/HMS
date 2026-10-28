<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Models\Department;
use App\Models\Item;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockReport
{
    public const EXPIRY_WARNING_DAYS = 90;

    /**
     * Every active item with its balance in each store, the nearest expiry, and recent use.
     *
     * @return Collection<int, array{item: Item, by_location: array<int, int>, total: int, nearest_expiry: ?Carbon, expired: int, used_30_days: int, status: string}>
     */
    public function levels(?string $search = null, bool $attentionOnly = false): Collection
    {
        $items = Item::query()
            ->where('is_active', true)
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->get();

        $balances = StockBatch::query()
            ->selectRaw('item_id, stock_location_id, SUM(quantity) as on_hand')
            ->groupBy('item_id', 'stock_location_id')
            ->get()
            ->groupBy('item_id');

        $nearestExpiry = StockBatch::query()
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->selectRaw('item_id, MIN(expiry_date) as nearest_expiry')
            ->groupBy('item_id')
            ->pluck('nearest_expiry', 'item_id');

        $expired = StockBatch::query()
            ->where('quantity', '>', 0)
            ->whereDate('expiry_date', '<', today())
            ->selectRaw('item_id, SUM(quantity) as expired')
            ->groupBy('item_id')
            ->pluck('expired', 'item_id');

        $used = StockMovement::query()
            ->whereIn('reason', StockReason::consumption())
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('item_id, SUM(quantity) as used')
            ->groupBy('item_id')
            ->pluck('used', 'item_id');

        return $items
            ->map(function (Item $item) use ($balances, $nearestExpiry, $expired, $used): array {
                $byLocation = collect($balances->get($item->id, []))
                    ->mapWithKeys(fn ($row): array => [(int) $row->stock_location_id => (int) $row->on_hand])
                    ->all();
                $total = array_sum($byLocation);
                $expiry = $nearestExpiry->get($item->id);
                $expiry = $expiry ? Carbon::parse($expiry) : null;

                $status = match (true) {
                    $total <= 0 => 'out_of_stock',
                    (int) $expired->get($item->id, 0) > 0 => 'expired',
                    $item->reorder_level > 0 && $total <= $item->reorder_level => 'low',
                    $expiry && $expiry->lte(today()->addDays(self::EXPIRY_WARNING_DAYS)) => 'expiring',
                    default => 'ok',
                };

                return [
                    'item' => $item,
                    'by_location' => $byLocation,
                    'total' => $total,
                    'nearest_expiry' => $expiry,
                    'expired' => (int) $expired->get($item->id, 0),
                    'used_30_days' => abs((int) $used->get($item->id, 0)),
                    'status' => $status,
                ];
            })
            ->when($attentionOnly, fn (Collection $rows) => $rows->where('status', '!=', 'ok'))
            ->values();
    }

    /**
     * Items this store has stocked before that are now at or below their reorder level.
     * The suggestion brings the store back up to twice the reorder level.
     *
     * @return Collection<int, array{item: Item, on_hand: int, main_on_hand: int, suggested: int}>
     */
    public function restockSuggestions(StockLocation $location): Collection
    {
        $main = StockLocation::query()
            ->where('facility_id', $location->facility_id)
            ->where('kind', 'main')
            ->first();

        $onHand = StockBatch::query()
            ->where('stock_location_id', $location->id)
            ->selectRaw('item_id, SUM(quantity) as on_hand')
            ->groupBy('item_id')
            ->pluck('on_hand', 'item_id');

        $mainOnHand = $main
            ? StockBatch::query()
                ->where('stock_location_id', $main->id)
                ->selectRaw('item_id, SUM(quantity) as on_hand')
                ->groupBy('item_id')
                ->pluck('on_hand', 'item_id')
            : collect();

        return Item::query()
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->whereIn('id', $onHand->keys())
            ->orderBy('name')
            ->get()
            ->map(fn (Item $item): array => [
                'item' => $item,
                'on_hand' => (int) $onHand->get($item->id, 0),
                'main_on_hand' => (int) $mainOnHand->get($item->id, 0),
                'suggested' => ($item->reorder_level * 2) - (int) $onHand->get($item->id, 0),
            ])
            ->filter(fn (array $row): bool => $row['on_hand'] <= $row['item']->reorder_level)
            ->values();
    }

    /**
     * Every movement of one item, newest first, with the balance left after each one.
     */
    public function card(Item $item, ?int $locationId, ?CarbonInterface $start, ?CarbonInterface $end, int $perPage = 50): LengthAwarePaginator
    {
        $movements = StockMovement::query()
            ->with(['location', 'batch', 'patient', 'department', 'performer', 'approver', 'reference'])
            ->where('item_id', $item->id)
            ->when($locationId, fn ($query) => $query->where('stock_location_id', $locationId))
            ->when($start, fn ($query) => $query->where('created_at', '>=', $start->copy()->startOfDay()))
            ->when($end, fn ($query) => $query->where('created_at', '<=', $end->copy()->endOfDay()))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $first = $movements->first();

        if (! $first) {
            return $movements;
        }

        $current = (int) StockBatch::query()
            ->where('item_id', $item->id)
            ->when($locationId, fn ($query) => $query->where('stock_location_id', $locationId))
            ->sum('quantity');

        $newer = (int) StockMovement::query()
            ->where('item_id', $item->id)
            ->when($locationId, fn ($query) => $query->where('stock_location_id', $locationId))
            ->where('id', '>', $first->id)
            ->sum('quantity');

        $balance = $current - $newer;

        foreach ($movements as $movement) {
            $movement->setAttribute('balance_after', $balance);
            $balance -= $movement->quantity;
        }

        return $movements;
    }

    /**
     * Stock used up in a period, grouped by department, employee, or item.
     *
     * @param  array{department_id?: ?int, performed_by?: ?int, item_id?: ?int, reason?: ?string}  $filters
     * @return Collection<int, array{group: string, item: string, unit: string, quantity: int, value: int}>
     */
    public function usage(CarbonInterface $start, CarbonInterface $end, string $groupBy, array $filters = []): Collection
    {
        $reasons = filled($filters['reason'] ?? null)
            ? [StockReason::from($filters['reason'])]
            : StockReason::consumption();

        $groupColumn = match ($groupBy) {
            'employee' => 'stock_movements.performed_by',
            'item' => 'stock_movements.item_id',
            default => 'stock_movements.department_id',
        };

        $rows = StockMovement::query()
            ->join('stock_batches', 'stock_batches.id', '=', 'stock_movements.stock_batch_id')
            ->whereIn('stock_movements.reason', $reasons)
            ->where('stock_movements.quantity', '<', 0)
            ->whereBetween('stock_movements.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($filters['department_id'] ?? null, fn ($query, $id) => $query->where('stock_movements.department_id', $id))
            ->when($filters['performed_by'] ?? null, fn ($query, $id) => $query->where('stock_movements.performed_by', $id))
            ->when($filters['item_id'] ?? null, fn ($query, $id) => $query->where('stock_movements.item_id', $id))
            ->selectRaw($groupColumn.' as group_id, stock_movements.item_id as item_id, SUM(stock_movements.quantity) as quantity, SUM(stock_movements.quantity * stock_batches.unit_cost) as value')
            ->groupBy($groupColumn, 'stock_movements.item_id')
            ->get();

        $items = Item::query()->whereIn('id', $rows->pluck('item_id'))->get()->keyBy('id');
        $groupNames = match ($groupBy) {
            'employee' => User::query()->whereIn('id', $rows->pluck('group_id')->filter())->pluck('name', 'id'),
            'item' => $items->map->name,
            default => Department::query()->whereIn('id', $rows->pluck('group_id')->filter())->pluck('name', 'id'),
        };
        $unknown = $groupBy === 'employee' ? 'Not recorded' : 'No department';

        return $rows
            ->map(fn ($row): array => [
                'group' => (string) ($groupNames->get($row->group_id) ?? $unknown),
                'item' => (string) ($items->get($row->item_id)?->name ?? 'Unknown item'),
                'unit' => (string) ($items->get($row->item_id)?->unit ?? 'unit'),
                'quantity' => abs((int) $row->quantity),
                'value' => abs((int) $row->value),
            ])
            ->sortBy([['group', 'asc'], ['item', 'asc']])
            ->values();
    }
}
