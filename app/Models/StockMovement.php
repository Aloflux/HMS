<?php

namespace App\Models;

use App\Enums\StockReason;
use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use BelongsToFacility;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'item_id',
        'stock_batch_id',
        'stock_location_id',
        'quantity',
        'reason',
        'reference_type',
        'reference_id',
        'patient_id',
        'department_id',
        'notes',
        'performed_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'reason' => StockReason::class,
            'quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * A short name for the document that caused this movement.
     */
    public function referenceLabel(): string
    {
        $reference = $this->reference;

        return match (true) {
            $reference === null => '—',
            filled($reference->getAttribute('number')) => (string) $reference->getAttribute('number'),
            $reference instanceof PrescriptionItem => 'Prescription',
            $reference instanceof Admission => 'Admission',
            $reference instanceof StockAdjustment => 'Adjustment #'.$reference->getKey(),
            default => class_basename($reference).' #'.$reference->getKey(),
        };
    }
}
