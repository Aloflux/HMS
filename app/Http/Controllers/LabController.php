<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Services\EncounterService;
use App\Services\SmsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabController extends Controller
{
    /**
     * @var list<string>
     */
    private const WAITING = ['ordered', 'resulted'];

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString() === 'verified' ? 'verified' : 'waiting';
        $search = $request->string('q')->trim()->toString();
        $today = now()->startOfDay();

        $matchesPatient = function ($query) use ($search): void {
            $query->where('visit_number', 'like', "%{$search}%")
                ->orWhereHas('patient', function ($patient) use ($search): void {
                    $patient->where('patient_number', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('given_name', 'like', "%{$search}%");
                });
        };

        $waitingItems = LabRequestItem::query()->whereIn('status', self::WAITING);

        return view('lab.index', [
            'tab' => $tab,
            'search' => $search,
            'stats' => [
                'waiting' => (clone $waitingItems)->count(),
                'urgent' => LabRequest::query()
                    ->whereHas('items', fn ($items) => $items->whereIn('status', self::WAITING))
                    ->whereHas('visit', fn ($visit) => $visit->whereIn('priority', [Priority::Urgent, Priority::Emergency]))
                    ->count(),
                'oldest' => (clone $waitingItems)->min('created_at'),
                'verifiedToday' => LabRequestItem::query()->where('status', 'verified')->where('verified_at', '>=', $today)->count(),
                'flaggedToday' => LabRequestItem::query()
                    ->where('status', 'verified')
                    ->where('verified_at', '>=', $today)
                    ->whereIn('result_flag', ['abnormal', 'critical'])
                    ->count(),
            ],
            'requests' => $tab === 'waiting'
                ? LabRequest::query()
                    ->whereHas('items', fn ($items) => $items->whereIn('status', self::WAITING))
                    ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search, $matchesPatient): void {
                        $inner->whereHas('visit', $matchesPatient)
                            ->orWhereHas('items.test', fn ($test) => $test->where('name', 'like', "%{$search}%"));
                    }))
                    ->with([
                        'visit.patient',
                        'requester',
                        'items' => fn ($items) => $items->whereIn('status', self::WAITING)->with('test'),
                    ])
                    ->orderByRaw("(select case priority when 'emergency' then 0 when 'urgent' then 1 else 2 end from visits where visits.id = lab_requests.visit_id)")
                    ->oldest()
                    ->get()
                : collect(),
            'results' => $tab === 'verified'
                ? LabRequestItem::query()
                    ->where('status', 'verified')
                    ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search, $matchesPatient): void {
                        $inner->whereHas('request.visit', $matchesPatient)
                            ->orWhereHas('test', fn ($test) => $test->where('name', 'like', "%{$search}%"));
                    }))
                    ->with(['test', 'request.visit.patient', 'verifier'])
                    ->latest('verified_at')
                    ->paginate(25)
                    ->withQueryString()
                : null,
        ]);
    }

    public function verify(Request $request, LabRequestItem $item, EncounterService $encounters, SmsNotifier $sms): RedirectResponse
    {
        $data = $request->validate([
            'result_value' => ['required', 'string', 'max:255'],
            'result_flag' => ['nullable', 'in:normal,abnormal,critical'],
        ]);

        $encounters->verifyLabResult($item, $request->user(), $data['result_value'], $data['result_flag'] ?? 'normal');
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
