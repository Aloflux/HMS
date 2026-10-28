<?php

namespace App\Models;

use App\Enums\BillingType;
use App\Enums\DosageForm;
use App\Models\Concerns\BelongsToFacility;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use BelongsToFacility, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'facility_id',
        'sku',
        'name',
        'category',
        'billing_type',
        'form',
        'unit',
        'unit_content',
        'pack_unit',
        'pack_size',
        'cash_price',
        'reorder_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'billing_type' => BillingType::class,
            'form' => DosageForm::class,
            'pack_size' => 'integer',
            'cash_price' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function hasPacks(): bool
    {
        return filled($this->pack_unit) && $this->pack_size > 1;
    }

    /**
     * The dispensing unit with what it holds, e.g. "bottle (100 ml)".
     */
    public function unitLabel(): string
    {
        return $this->unit.($this->unit_content ? ' ('.$this->unit_content.')' : '');
    }

    /**
     * The purchase pack, e.g. "tin of 1,000 tablets", or null when the item is bought by the unit.
     */
    public function packLabel(): ?string
    {
        if (! $this->hasPacks()) {
            return null;
        }

        return $this->pack_unit.' of '.number_format($this->pack_size).' '.Str::plural($this->unit, $this->pack_size);
    }

    /**
     * A quantity in dispensing units, with whole packs spelled out, e.g. "2,150 tablets (2 tins + 150)".
     */
    public function describeQuantity(int $quantity): string
    {
        $text = number_format($quantity).' '.Str::plural($this->unit, $quantity);

        if (! $this->hasPacks() || abs($quantity) < $this->pack_size) {
            return $text;
        }

        $packs = intdiv(abs($quantity), $this->pack_size);
        $loose = abs($quantity) % $this->pack_size;

        return $text.' ('.$packs.' '.Str::plural($this->pack_unit, $packs).($loose ? ' + '.number_format($loose) : '').')';
    }

    /**
     * Convert a quantity entered in packs or units to dispensing units.
     */
    public function toUnits(int $quantity, ?string $countIn): int
    {
        return $countIn === 'pack' ? $quantity * max(1, $this->pack_size) : $quantity;
    }

    /**
     * Convert a cost entered per pack or per unit to a cost per dispensing unit.
     */
    public function toUnitCost(int $cost, ?string $countIn): int
    {
        return $countIn === 'pack' ? (int) round($cost / max(1, $this->pack_size)) : $cost;
    }

    /**
     * What the pharmacist does to hand over this quantity.
     */
    public function dispensingInstruction(int $quantity): string
    {
        $source = $this->hasPacks() ? ' from the '.$this->pack_unit : '';

        return match (($this->form ?? DosageForm::Other)->handling()) {
            'count' => 'Count out '.number_format($quantity).' '.Str::plural($this->unit, $quantity).$source,
            'measure' => 'Measure out '.number_format($quantity).' '.$this->unit.$source,
            default => 'Hand over '.number_format($quantity).' '.Str::plural($this->unit, $quantity).($this->unit_content ? ' ('.$this->unit_content.($quantity > 1 ? ' each' : '').')' : ''),
        };
    }

    /**
     * What the browser needs to label quantity fields for this item.
     *
     * @return array{unit: string, units: string, pack_unit: ?string, pack_units: ?string, pack_size: int}
     */
    public function quantityMeta(): array
    {
        return [
            'unit' => $this->unit,
            'units' => Str::plural($this->unit),
            'pack_unit' => $this->hasPacks() ? $this->pack_unit : null,
            'pack_units' => $this->hasPacks() ? Str::plural($this->pack_unit) : null,
            'pack_size' => $this->hasPacks() ? $this->pack_size : 1,
        ];
    }
}
