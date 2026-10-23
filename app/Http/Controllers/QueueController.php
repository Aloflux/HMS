<?php

namespace App\Http\Controllers;

use App\Enums\QueueStage;
use App\Models\QueueEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(Request $request): View
    {
        $stage = QueueStage::tryFrom($request->string('stage')->toString());

        $entries = QueueEntry::query()
            ->with(['visit.patient', 'department'])
            ->whereIn('status', ['waiting', 'serving'])
            ->when($stage, fn ($query) => $query->where('stage', $stage))
            ->orderByRaw("CASE status WHEN 'serving' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE priority WHEN 'emergency' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END")
            ->orderBy('queued_at')
            ->get();

        return view('queues.index', [
            'entries' => $entries,
            'stage' => $stage,
            'stages' => QueueStage::cases(),
        ]);
    }

    public function call(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stage' => ['required', Rule::enum(QueueStage::class)],
        ]);

        $stage = QueueStage::from($data['stage']);

        abort_unless($request->user()->permits($stage->permission()), 403);

        $entry = QueueEntry::query()
            ->where('stage', $stage)
            ->where('status', 'waiting')
            ->orderByRaw("CASE priority WHEN 'emergency' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END")
            ->orderBy('queued_at')
            ->first();

        if (! $entry) {
            return back()->with('error', 'Nobody is waiting in that queue.');
        }

        $entry->update(['status' => 'serving', 'called_at' => now()]);

        return redirect()->route('visits.show', $entry->visit_id)->with('status', 'Patient called.');
    }

    public function complete(Request $request, QueueEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->permits($entry->stage->permission()), 403);

        if (! in_array($entry->status, ['waiting', 'serving'], true)) {
            return back()->with('error', 'That patient has already left this queue.');
        }

        $entry->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('status', 'Marked as done.');
    }
}
