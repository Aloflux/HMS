<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispensation extends Model
{
    use BelongsToFacility, RecordsAudit;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'prescription_item_id', 'item_id', 'stock_batch_id', 'quantity', 'dispensed_by', 'bill_line_id', 'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function billLine(): BelongsTo
    {
        return $this->belongsTo(BillLine::class);
    }
}
