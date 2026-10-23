<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Bill;
use App\Models\CashierShift;
use App\Models\Waiver;
use App\Services\CashierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashierController extends Controller
{
    public function __construct(private CashierService $cashier) {}

    public function index(Request $request): View
    {
        $shift = CashierShift::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        return view('cashier.index', [
            'shift' => $shift,
            'bills' => Bill::query()
                ->with(['patient', 'visit', 'lines', 'payments'])
                ->whereHas('visit', fn ($query) => $query->where('payer', 'cash'))
                ->whereIn('status', ['open', 'partial'])
                ->latest()
                ->limit(30)
                ->get(),
            'waivers' => Waiver::query()
                ->with(['bill.patient', 'bill.visit', 'requester', 'decider'])
                ->where(fn ($query) => $query->where('status', 'pending')->orWhere('updated_at', '>=', now()->subDay()))
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->latest()
                ->get(),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function openShift(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opening_float' => ['required', 'integer', 'min:0'],
        ]);

        $this->cashier->openShift($request->user(), (int) $data['opening_float']);

        return back()->with('status', 'Shift opened.');
    }

    public function closeShift(Request $request, CashierShift $shift): RedirectResponse
    {
        abort_unless($shift->user_id === $request->user()->id && $shift->status === 'open', 403);

        $data = $request->validate([
            'cash_counted' => ['required', 'integer', 'min:0'],
        ]);

        $closed = $this->cashier->closeShift($shift, (int) $data['cash_counted']);

        return back()->with('status', 'Shift closed. Variance UGX '.number_format($closed->variance).'.');
    }

    public function pay(Request $request, Bill $bill): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        $payment = $this->cashier->takePayment(
            $bill,
            $request->user(),
            PaymentMethod::from($data['method']),
            (int) $data['amount'],
            $data['reference'] ?? null,
        );

        return redirect()->route('receipts.show', $payment)->with('status', 'Payment recorded.');
    }

    public function requestWaiver(Request $request, Bill $bill): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->cashier->requestWaiver($bill, $request->user(), (int) $data['amount'], $data['reason']);

        return back()->with('status', 'Waiver sent for approval.');
    }

    public function approveWaiver(Request $request, Waiver $waiver): RedirectResponse
    {
        $this->cashier->approveWaiver($waiver, $request->user());

        return back()->with('status', 'Waiver approved.');
    }

    public function rejectWaiver(Request $request, Waiver $waiver): RedirectResponse
    {
        $this->cashier->rejectWaiver($waiver, $request->user());

        return back()->with('status', 'Waiver rejected.');
    }
}
