<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\QueueStage;
use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueEntry extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'visit_id',
        'department_id',
        'stage',
        'status',
        'priority',
        'queued_at',
        'called_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'stage' => QueueStage::class,
            'priority' => Priority::class,
            'queued_at' => 'datetime',
            'called_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
