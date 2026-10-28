<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
    public function __construct(
        private SequenceGenerator $sequences,
        private StockLedger $stock,
    ) {}

    /**
     * Quantities and costs are in dispensing units. The pack size is kept on the line so the order
     * still reads in packs if the item's pack changes later.
     *
     * @param  list<array{item_id: int, quantity: int, unit_cost: int, pack_size?: int}>  $lines
     */
    public function order(Supplier $supplier, User $actor, array $lines, ?string $notes = null): PurchaseOrder
    {
        return DB::transaction(function () use ($supplier, $actor, $lines, $notes): PurchaseOrder {
            $facility = $supplier->facility;

            $order = PurchaseOrder::query()->create([
                'facility_id' => $facility->id,
                'supplier_id' => $supplier->id,
                'number' => $this->sequences->next($facility, 'purchase_order'),
                'status' => 'sent',
                'ordered_at' => now(),
                'ordered_by' => $actor->id,
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $order->lines()->create([
                    'facility_id' => $facility->id,
                    'item_id' => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'pack_size' => $line['pack_size'] ?? 1,
                    'unit_cost' => $line['unit_cost'],
                ]);
            }

            return $order;
        });
    }

    /**
     * @param  array{batch_number: string, expiry_date: ?string, quantity: int}  $receipt
     */
    public function receive(PurchaseOrder $order, int $lineId, User $actor, array $receipt): GoodsReceipt
    {
        return DB::transaction(function () use ($order, $lineId, $actor, $receipt): GoodsReceipt {
            $line = $order->lines()->whereKey($lineId)->first();

            if (! $line) {
                throw ValidationException::withMessages([
                    'purchase_order_line_id' => 'That purchase order line was not found.',
                ]);
            }

            $outstanding = $line->quantity - $line->received_quantity;

            if ($receipt['quantity'] > $outstanding) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$outstanding} are still outstanding on this line.",
                ]);
            }

            $location = StockLocation::query()
                ->where('facility_id', $order->facility_id)
                ->where('kind', 'main')
                ->first();

            if (! $location) {
                throw ValidationException::withMessages([
                    'quantity' => 'This facility has no main store.',
                ]);
            }

            $goods = GoodsReceipt::query()->create([
                'facility_id' => $order->facility_id,
                'purchase_order_id' => $order->id,
                'number' => $this->sequences->next($order->facility, 'goods_receipt'),
                'received_by' => $actor->id,
                'received_at' => now(),
            ]);

            $goods->lines()->create([
                'facility_id' => $order->facility_id,
                'purchase_order_line_id' => $line->id,
                'item_id' => $line->item_id,
                'batch_number' => $receipt['batch_number'],
                'expiry_date' => $receipt['expiry_date'] ?? null,
                'quantity' => $receipt['quantity'],
                'unit_cost' => $line->unit_cost,
            ]);

            $this->stock->receive(
                $line->item,
                $location,
                $receipt['batch_number'],
                isset($receipt['expiry_date']) ? Carbon::parse($receipt['expiry_date']) : null,
                (int) $receipt['quantity'],
                (int) $line->unit_cost,
                StockReason::Purchase,
                $goods,
            );

            $line->increment('received_quantity', $receipt['quantity']);

            $order->load('lines');
            $complete = $order->lines->every(fn ($orderLine): bool => $orderLine->received_quantity >= $orderLine->quantity);
            $order->update(['status' => $complete ? 'received' : 'partial']);

            return $goods;
        });
    }

    /**
     * @param  list<array{item_id: int, quantity: int}>  $lines
     */
    public function requestStock(StockLocation $location, User $actor, array $lines, ?string $notes = null): Requisition
    {
        return DB::transaction(function () use ($location, $actor, $lines, $notes): Requisition {
            $requisition = Requisition::query()->create([
                'facility_id' => $location->facility_id,
                'stock_location_id' => $location->id,
                'number' => $this->sequences->next($location->facility, 'requisition'),
                'status' => 'submitted',
                'requested_by' => $actor->id,
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $requisition->lines()->create([
                    'facility_id' => $location->facility_id,
                    'item_id' => $line['item_id'],
                    'quantity_requested' => $line['quantity'],
                ]);
            }

            return $requisition;
        });
    }

    /**
     * Issue what the main store can spare. A line the store cannot fully cover stays open
     * so the rest can be issued once new stock arrives.
     */
    public function issueRequisition(Requisition $requisition, User $actor): void
    {
        DB::transaction(function () use ($requisition, $actor): void {
            $requisition = Requisition::query()->whereKey($requisition->id)->lockForUpdate()->firstOrFail();

            if (! in_array($requisition->status, ['submitted', 'partial'], true)) {
                throw ValidationException::withMessages([
                    'requisition' => 'Only a submitted or part-issued requisition can be issued.',
                ]);
            }

            $main = $this->mainStore($requisition->facility_id, 'requisition');
            $issuedNow = 0;

            foreach ($requisition->lines()->with('item')->get() as $line) {
                $outstanding = $line->quantity_requested - $line->quantity_issued;
                $quantity = min($outstanding, $this->stock->onHand($line->item, $main));

                if ($quantity < 1) {
                    continue;
                }

                $this->stock->transfer($main, $requisition->location, $line->item, $quantity, $requisition);
                $line->increment('quantity_issued', $quantity);
                $issuedNow += $quantity;
            }

            if ($issuedNow === 0) {
                throw ValidationException::withMessages([
                    'requisition' => 'The main store has none of the requested items in stock.',
                ]);
            }

            $complete = $requisition->lines()->whereColumn('quantity_issued', '<', 'quantity_requested')->doesntExist();

            $requisition->update([
                'status' => $complete ? 'issued' : 'partial',
                'issued_by' => $actor->id,
            ]);
        });
    }

    /**
     * Send unused or unwanted stock from a department store back to the main store.
     */
    public function returnToMainStore(StockLocation $from, Item $item, int $quantity, ?string $notes = null): void
    {
        DB::transaction(function () use ($from, $item, $quantity, $notes): void {
            $main = $this->mainStore($from->facility_id, 'stock_location_id');

            $this->stock->transfer($from, $main, $item, $quantity, null, StockReason::Return, StockReason::Return, $notes);
        });
    }

    public function requestAdjustment(StockLocation $location, Item $item, ?StockBatch $batch, int $quantity, string $reason, User $actor, ?string $notes = null): StockAdjustment
    {
        if (! in_array($reason, ['expiry', 'damage', 'loss', 'count'], true)) {
            throw ValidationException::withMessages([
                'reason_code' => 'Choose expiry, damage, loss, or count.',
            ]);
        }

        if ($quantity === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'An adjustment needs a non-zero quantity.',
            ]);
        }

        return StockAdjustment::query()->create([
            'facility_id' => $location->facility_id,
            'stock_location_id' => $location->id,
            'item_id' => $item->id,
            'stock_batch_id' => $batch?->id,
            'quantity' => $quantity,
            'reason_code' => $reason,
            'status' => 'pending',
            'requested_by' => $actor->id,
            'notes' => $notes,
        ]);
    }

    public function approveAdjustment(StockAdjustment $adjustment, User $approver): void
    {
        DB::transaction(function () use ($adjustment, $approver): void {
            if ($adjustment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'adjustment' => 'This adjustment is no longer pending.',
                ]);
            }

            $location = $adjustment->location;
            $item = $adjustment->item;

            if ($adjustment->quantity > 0) {
                $this->stock->receive(
                    $item,
                    $location,
                    $adjustment->batch?->batch_number ?? ('ADJ-'.$adjustment->id),
                    $adjustment->batch?->expiry_date,
                    $adjustment->quantity,
                    (int) ($adjustment->batch?->unit_cost ?? 0),
                    StockReason::Adjustment,
                    $adjustment,
                    null,
                    $approver->id,
                    $adjustment->notes,
                );
            } else {
                $this->stock->consume(
                    $location,
                    $item,
                    abs($adjustment->quantity),
                    StockReason::Adjustment,
                    $adjustment,
                    null,
                    $approver->id,
                    $adjustment->notes,
                );
            }

            $adjustment->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
            ]);
        });
    }

    private function mainStore(int $facilityId, string $errorKey): StockLocation
    {
        $main = StockLocation::query()
            ->where('facility_id', $facilityId)
            ->where('kind', 'main')
            ->first();

        if (! $main) {
            throw ValidationException::withMessages([
                $errorKey => 'This facility has no main store.',
            ]);
        }

        return $main;
    }
}
