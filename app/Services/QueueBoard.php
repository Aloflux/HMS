<?php

namespace App\Services;

use App\Enums\QueueStage;
use App\Models\QueueEntry;
use App\Models\Visit;

class QueueBoard
{
    public function enqueue(Visit $visit, QueueStage $stage): QueueEntry
    {
        $existing = QueueEntry::query()
            ->where('visit_id', $visit->id)
            ->where('stage', $stage)
            ->whereIn('status', ['waiting', 'serving'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return QueueEntry::query()->create([
            'facility_id' => $visit->facility_id,
            'visit_id' => $visit->id,
            'department_id' => $visit->department_id,
            'stage' => $stage,
            'status' => 'waiting',
            'priority' => $visit->priority,
            'queued_at' => now(),
        ]);
    }

    public function complete(Visit $visit, QueueStage $stage): void
    {
        QueueEntry::query()
            ->where('visit_id', $visit->id)
            ->where('stage', $stage)
            ->whereIn('status', ['waiting', 'serving'])
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
    }
}
