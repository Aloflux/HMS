<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFacility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    use BelongsToFacility;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id', 'purchase_order_id', 'item_id', 'quantity', 'pack_size', 'unit_cost', 'received_quantity',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * The item as it was packed when ordered, so the order keeps reading in the packs it was placed in.
     */
    public function orderedItem(): Item
    {
        $item = $this->item->replicate();
        $item->pack_size = max(1, (int) $this->pack_size);
        $item->pack_unit = $this->pack_size > 1 ? ($this->item->pack_unit ?? 'pack') : null;

        return $item;
    }
}
