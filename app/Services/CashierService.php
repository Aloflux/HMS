<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Bill;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waiver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashierService
{
    public function __construct(
        private BillingService $billing,
        private DispensingService $dispensing,
        private EncounterService $encounters,
        private SmsNotifier $sms,
    ) {}

    public function openShift(User $user, int $openingFloat): CashierShift
    {
        $existing = CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return $existing;
        }

        return CashierShift::query()->create([
            'facility_id' => $user->facility_id,
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_float' => $openingFloat,
            'status' => 'open',
        ]);
    }

    public function takePayment(Bill $bill, User $user, PaymentMethod $method, int $amount, ?string $reference = null): Payment
    {
        return DB::transaction(function () use ($bill, $user, $method, $amount, $reference): Payment {
            if ($amount < 1) {
                throw ValidationException::withMessages([
                    'amount' => 'Enter an amount greater than zero.',
                ]);
            }

            if (! $method->sitsInDrawer() && blank($reference)) {
                throw ValidationException::withMessages([
                    'reference' => 'Mobile money and card payments need a reference.',
                ]);
            }

            $shift = CashierShift::query()
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->first();

            if (! $shift) {
                throw ValidationException::withMessages([
                    'shift' => 'Open a cashier shift before taking payment.',
                ]);
            }

            $payment = Payment::query()->create([
                'facility_id' => $bill->facility_id,
                'bill_id' => $bill->id,
                'cashier_shift_id' => $shift->id,
                'method' => $method,
                'amount' => $amount,
                'reference' => $reference,
                'status' => 'confirmed',
                'received_by' => $user->id,
            ]);

            $this->billing->refreshStatus($bill->fresh());
            $visit = $bill->visit()->first();
            $this->dispensing->releaseIfSettled($visit);
            $this->encounters->closeIfSettled($visit->fresh());

            $this->sms->queue(
                $bill->patient,
                'payment',
                'Payment of UGX '.number_format($amount).' received for visit '.$visit->visit_number.'.',
            );

            return $payment;
        });
    }

    public function closeShift(CashierShift $shift, int $cashCounted): CashierShift
    {
        $cashTaken = (int) $shift->payments()->where('method', PaymentMethod::Cash->value)->sum('amount');
        $expected = (int) $shift->opening_float + $cashTaken;

        $shift->update([
            'status' => 'closed',
            'closed_at' => now(),
            'cash_expected' => $expected,
            'cash_counted' => $cashCounted,
            'variance' => $cashCounted - $expected,
        ]);

        return $shift->fresh();
    }

    public function requestWaiver(Bill $bill, User $user, int $amount, string $reason): Waiver
    {
        $bill->load(['lines', 'payments']);

        if ($amount < 1 || $amount > $bill->cashBalance()) {
            throw ValidationException::withMessages([
                'amount' => 'The waiver must be within the cash balance.',
            ]);
        }

        return Waiver::query()->create([
            'facility_id' => $bill->facility_id,
            'bill_id' => $bill->id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);
    }

    public function approveWaiver(Waiver $waiver, User $approver): void
    {
        DB::transaction(function () use ($waiver, $approver): void {
            if ($waiver->status !== 'pending') {
                throw ValidationException::withMessages([
                    'waiver' => 'This waiver is no longer pending.',
                ]);
            }

            $bill = $waiver->bill()->with(['lines', 'payments'])->first();
            $bill->increment('waived_amount', $waiver->amount);
            $waiver->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
            ]);

            $this->billing->refreshStatus($bill->fresh());
            $visit = $bill->visit;
            $this->dispensing->releaseIfSettled($visit);
            $this->encounters->closeIfSettled($visit->fresh());
        });
    }
}
