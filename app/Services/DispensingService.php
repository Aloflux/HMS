<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Enums\QueueStage;
use App\Enums\StockReason;
use App\Models\Dispensation;
use App\Models\Item;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispensingService
{
    public function __construct(
        private StockLedger $stock,
        private BillingService $billing,
        private PriceBook $prices,
        private QueueBoard $queues,
    ) {}

    public function dispense(PrescriptionItem $line, User $actor, int $quantity, ?int $substituteItemId = null): void
    {
        DB::transaction(function () use ($line, $actor, $quantity, $substituteItemId): void {
            $line->load('item', 'prescription.visit');

            if ($line->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'quantity' => 'This prescription line is cancelled.',
                ]);
            }

            if ($quantity > $line->remainingQuantity()) {
                throw ValidationException::withMessages([
                    'quantity' => 'That quantity is more than the prescription still allows.',
                ]);
            }

            $item = $line->item;

            if ($substituteItemId) {
                $item = Item::query()->whereKey($substituteItemId)->first();

                if (! $item || $item->billing_type !== BillingType::BillableMedicine) {
                    throw ValidationException::withMessages([
                        'substitute_item_id' => 'The substitute must be a billable medicine at this facility.',
                    ]);
                }
            }

            if ($item->billing_type !== BillingType::BillableMedicine) {
                throw ValidationException::withMessages([
                    'quantity' => 'Only billable medicines are dispensed from the pharmacy.',
                ]);
            }

            $location = StockLocation::query()
                ->where('facility_id', $line->facility_id)
                ->where('kind', 'pharmacy')
                ->first();

            if (! $location) {
                throw ValidationException::withMessages([
                    'quantity' => 'This facility has no pharmacy store.',
                ]);
            }

            $visit = $line->prescription->visit;
            $taken = $this->stock->consume(
                $location,
                $item,
                $quantity,
                StockReason::Dispense,
                $line,
                $visit->patient_id,
            );

            $price = $this->prices->forItem($item, $visit);

            foreach ($taken as $row) {
                $dispensation = Dispensation::query()->create([
                    'facility_id' => $line->facility_id,
                    'prescription_item_id' => $line->id,
                    'item_id' => $item->id,
                    'stock_batch_id' => $row['batch']->id,
                    'quantity' => $row['quantity'],
                    'dispensed_by' => $actor->id,
                ]);

                $billLine = $this->billing->charge(
                    $visit,
                    $item->name,
                    'dispensation',
                    $dispensation->id,
                    $row['quantity'],
                    $price,
                );

                $dispensation->update(['bill_line_id' => $billLine->id]);
            }

            $updates = [
                'status' => $line->fresh()->remainingQuantity() === 0 ? 'dispensed' : 'pending',
            ];

            if ($substituteItemId) {
                $updates['substituted_item_id'] = $substituteItemId;
            }

            $line->update($updates);
            $this->syncPrescription($line->prescription);
            $this->releaseIfSettled($visit->fresh());
        });
    }

    public function cancel(PrescriptionItem $line): void
    {
        if ($line->dispensations()->exists()) {
            throw ValidationException::withMessages([
                'prescription' => 'Dispensed medicines cannot be cancelled. Return unused stock instead.',
            ]);
        }

        $line->update(['status' => 'cancelled']);
        $this->syncPrescription($line->prescription);
    }

    public function releaseIfSettled(Visit $visit): void
    {
        $visit->load(['bill.lines', 'bill.payments']);

        if ($visit->payer->settlesAtCashier() && $visit->bill && $visit->bill->cashBalance() > 0) {
            return;
        }

        Dispensation::query()
            ->whereIn(
                'prescription_item_id',
                PrescriptionItem::query()->whereIn('prescription_id', $visit->prescriptions()->select('id'))->select('id'),
            )
            ->whereNull('collected_at')
            ->update(['collected_at' => now()]);

        $pending = PrescriptionItem::query()
            ->whereIn('prescription_id', $visit->prescriptions()->select('id'))
            ->where('status', 'pending')
            ->exists();

        if (! $pending) {
            $this->queues->complete($visit, QueueStage::Pharmacy);
        }
    }

    private function syncPrescription(Prescription $prescription): void
    {
        $statuses = $prescription->items()->pluck('status');

        $status = match (true) {
            $statuses->every(fn (string $status): bool => in_array($status, ['dispensed', 'cancelled'], true)) => 'dispensed',
            $statuses->contains('dispensed') => 'partial',
            default => 'pending',
        };

        $prescription->update(['status' => $status]);
    }
}
