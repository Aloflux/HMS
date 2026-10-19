<?php

namespace App\Services;

use App\Enums\QueueStage;
use App\Models\Bill;
use App\Models\BillLine;
use App\Models\Visit;
use Illuminate\Validation\ValidationException;

class BillingService
{
    public function __construct(private QueueBoard $queues) {}

    public function openFor(Visit $visit): Bill
    {
        return Bill::query()->firstOrCreate(
            ['visit_id' => $visit->id],
            [
                'facility_id' => $visit->facility_id,
                'patient_id' => $visit->patient_id,
                'status' => 'open',
                'currency' => 'UGX',
            ],
        );
    }

    public function charge(
        Visit $visit,
        string $description,
        string $sourceType,
        int $sourceId,
        int $quantity,
        int $unitPrice,
        bool $exemptPreauth = false,
    ): BillLine {
        $existing = BillLine::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $amount = $quantity * $unitPrice;
        $this->assertCanCharge($visit, $amount, $exemptPreauth);

        $bill = $this->openFor($visit);

        $line = BillLine::query()->create([
            'facility_id' => $visit->facility_id,
            'bill_id' => $bill->id,
            'description' => $description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => $amount,
            'payer' => $visit->payer,
        ]);

        $this->refreshStatus($bill);

        if ($visit->payer->settlesAtCashier() && $amount > 0) {
            $this->queues->enqueue($visit, QueueStage::Cashier);
        }

        return $line;
    }

    public function assertCanCharge(Visit $visit, int $amount, bool $exemptPreauth = false): void
    {
        $scheme = $visit->scheme;

        if (! $scheme || $visit->payer->settlesAtCashier()) {
            return;
        }

        if (! $exemptPreauth && $scheme->requires_preauthorisation) {
            $approved = $visit->preauthorisations()->where('status', 'approved')->exists();

            if (! $approved) {
                throw ValidationException::withMessages([
                    'preauthorisation' => 'This scheme needs an approved preauthorisation before further charges.',
                ]);
            }
        }

        if ($scheme->cover_limit !== null) {
            $used = (int) BillLine::query()
                ->where('waived', false)
                ->whereIn('bill_id', Bill::query()->where('visit_id', $visit->id)->select('id'))
                ->sum('amount');

            if ($used + $amount > $scheme->cover_limit) {
                throw ValidationException::withMessages([
                    'cover' => 'This charge exceeds the remaining scheme cover limit.',
                ]);
            }
        }
    }

    public function refreshStatus(Bill $bill): void
    {
        $bill->loadMissing('visit');
        $bill->load(['lines', 'payments']);

        if (! $bill->visit->payer->settlesAtCashier()) {
            $bill->update(['status' => 'open']);

            return;
        }

        $balance = $bill->cashBalance();

        $status = match (true) {
            $bill->lines->isEmpty() => 'open',
            $balance === 0 => 'paid',
            $bill->payments->isNotEmpty() || (int) $bill->waived_amount > 0 => 'partial',
            default => 'open',
        };

        $bill->update(['status' => $status]);
    }
}
