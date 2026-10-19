<?php

namespace App\Http\Controllers;

use App\Models\LabRequestItem;
use App\Services\EncounterService;
use App\Services\SmsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabController extends Controller
{
    public function index(): View
    {
        return view('lab.index', [
            'items' => LabRequestItem::query()
                ->with(['test', 'request.visit.patient'])
                ->whereIn('status', ['ordered', 'resulted'])
                ->latest()
                ->get(),
        ]);
    }

    public function verify(Request $request, LabRequestItem $item, EncounterService $encounters, SmsNotifier $sms): RedirectResponse
    {
        $data = $request->validate([
            'result_value' => ['required', 'string', 'max:255'],
        ]);

        $encounters->verifyLabResult($item, $request->user(), $data['result_value']);
        $item->load('request.visit.patient', 'test');

        $sms->queue(
            $item->request->visit->patient,
            'lab_result',
            $item->test->name.' result is ready for visit '.$item->request->visit->visit_number.'.',
        );

        return back()->with('status', 'Result verified.');
    }

    public function cancel(LabRequestItem $item, EncounterService $encounters): RedirectResponse
    {
        $encounters->cancelLabItem($item);

        return back()->with('status', 'Lab test cancelled.');
    }
}
