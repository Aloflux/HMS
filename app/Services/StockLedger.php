<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Models\Item;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\StockMovement;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StockLedger
{
    /**
     * @return list<array{batch: StockBatch, quantity: int}>
     */
    public function consume(
        StockLocation $location,
        Item $item,
        int $quantity,
        StockReason $reason,
        Model $reference,
        ?int $patientId = null,
        ?int $approvedBy = null,
        ?string $notes = null,
    ): array {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be at least 1.',
            ]);
        }

        if ($location->facility_id !== $item->facility_id) {
            throw ValidationException::withMessages([
                'item' => 'That item does not belong to this facility.',
            ]);
        }

        $batches = StockBatch::query()
            ->where('stock_location_id', $location->id)
            ->where('item_id', $item->id)
            ->where('quantity', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->lockForUpdate()
            ->get();

        $available = (int) $batches->sum('quantity');

        if ($available < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$available} {$item->unit} of {$item->name} are in {$location->name}.",
            ]);
        }

        $taken = [];
        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining === 0) {
                break;
            }

            $slice = min($remaining, $batch->quantity);
            $batch->quantity -= $slice;
            $batch->save();

            $this->move($batch, -$slice, $reason, $reference, $patientId, $approvedBy, $notes);

            $taken[] = ['batch' => $batch, 'quantity' => $slice];
            $remaining -= $slice;
        }

        return $taken;
    }

    public function receive(
        Item $item,
        StockLocation $location,
        string $batchNumber,
        ?CarbonInterface $expiry,
        int $quantity,
        int $unitCost,
        StockReason $reason,
        Model $reference,
        ?int $patientId = null,
        ?int $approvedBy = null,
        ?string $notes = null,
    ): StockBatch {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be at least 1.',
            ]);
        }

        $batch = StockBatch::query()->firstOrCreate(
            [
                'stock_location_id' => $location->id,
                'item_id' => $item->id,
                'batch_number' => $batchNumber,
            ],
            [
                'facility_id' => $location->facility_id,
                'expiry_date' => $expiry,
                'unit_cost' => $unitCost,
                'quantity' => 0,
            ],
        );

        $batch->quantity += $quantity;
        $batch->save();

        $this->move($batch, $quantity, $reason, $reference, $patientId, $approvedBy, $notes);

        return $batch;
    }

    public function transfer(StockLocation $from, StockLocation $to, Item $item, int $quantity, Model $reference): void
    {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages([
                'stock_location_id' => 'Choose a different receiving store.',
            ]);
        }

        if ($from->facility_id !== $to->facility_id) {
            throw ValidationException::withMessages([
                'stock_location_id' => 'Stock cannot move between facilities.',
            ]);
        }

        $taken = $this->consume($from, $item, $quantity, StockReason::TransferOut, $reference);

        foreach ($taken as $row) {
            $this->receive(
                $item,
                $to,
                $row['batch']->batch_number,
                $row['batch']->expiry_date,
                $row['quantity'],
                (int) $row['batch']->unit_cost,
                StockReason::TransferIn,
                $reference,
            );
        }
    }

    public function onHand(Item $item, ?StockLocation $location = null): int
    {
        return (int) StockBatch::query()
            ->where('item_id', $item->id)
            ->when($location, fn ($query) => $query->where('stock_location_id', $location->id))
            ->sum('quantity');
    }

    private function move(
        StockBatch $batch,
        int $quantity,
        StockReason $reason,
        Model $reference,
        ?int $patientId,
        ?int $approvedBy,
        ?string $notes,
    ): void {
        StockMovement::query()->create([
            'facility_id' => $batch->facility_id,
            'item_id' => $batch->item_id,
            'stock_batch_id' => $batch->id,
            'stock_location_id' => $batch->stock_location_id,
            'quantity' => $quantity,
            'reason' => $reason,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'patient_id' => $patientId,
            'notes' => $notes,
            'performed_by' => Auth::id(),
            'approved_by' => $approvedBy,
        ]);
    }
}
