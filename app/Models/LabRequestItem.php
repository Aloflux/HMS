<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabRequestItem extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'lab_request_id', 'lab_test_id', 'status', 'result_value', 'result_flag', 'resulted_by', 'verified_by', 'resulted_at', 'verified_at', 'bill_line_id',
    ];

    protected function casts(): array
    {
        return [
            'resulted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class, 'lab_request_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    public function billLine(): BelongsTo
    {
        return $this->belongsTo(BillLine::class);
    }
}
