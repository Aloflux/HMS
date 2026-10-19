<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Payment $payment): View
    {
        $payment->load(['bill.patient', 'bill.visit', 'bill.lines', 'bill.payments']);

        return view('receipts.show', [
            'payment' => $payment,
        ]);
    }
}
