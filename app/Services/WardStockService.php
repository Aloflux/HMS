<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Models\Admission;
use App\Models\Item;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\WardConsumption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WardStockService
{
    public function __construct(
        private StockLedger $stock,
        private BillingService $billing,
        private PriceBook $prices,
    ) {}

    public function issue(Admission $admission, Item $item, StockLocation $location, int $quantity, User $actor, ?string $notes = null): WardConsumption
    {
        return DB::transaction(function () use ($admission, $item, $location, $quantity, $actor, $notes): WardConsumption {
            if ($location->kind !== 'ward' || $location->facility_id !== $admission->facility_id) {
                throw ValidationException::withMessages([
                    'stock_location_id' => 'Ward issues must come from a ward store at this facility.',
                ]);
            }

            $visit = $admission->visit;
            $reason = $item->billing_type->chargesPatient() ? StockReason::WardIssue : StockReason::InternalUse;

            $this->stock->consume($location, $item, $quantity, $reason, $admission, $admission->patient_id, null, $notes);

            $consumption = WardConsumption::query()->create([
                'facility_id' => $admission->facility_id,
                'admission_id' => $admission->id,
                'stock_location_id' => $location->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'billing_type' => $item->billing_type,
                'department_id' => $location->department_id,
                'recorded_by' => $actor->id,
                'notes' => $notes,
            ]);

            if ($item->billing_type->chargesPatient()) {
                $line = $this->billing->charge(
                    $visit,
                    $item->name,
                    'ward_consumption',
                    $consumption->id,
                    $quantity,
                    $this->prices->forItem($item, $visit),
                );

                $consumption->update(['bill_line_id' => $line->id]);
            }

            return $consumption;
        });
    }
}
