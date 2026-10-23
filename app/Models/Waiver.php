<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waiver extends Model
{
    use BelongsToFacility, RecordsAudit;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'bill_id', 'amount', 'reason', 'status', 'requested_by', 'approved_by',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
