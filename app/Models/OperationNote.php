<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationNote extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'theatre_booking_id', 'findings', 'procedure_notes', 'surgeon_id', 'recorded_at',
    ];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TheatreBooking::class, 'theatre_booking_id');
    }
}
