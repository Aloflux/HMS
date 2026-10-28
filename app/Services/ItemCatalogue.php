<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Enums\DosageForm;
use App\Enums\StockReason;
use App\Models\Item;
use App\Models\StockBatch;
use App\Models\StockLocation;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ItemCatalogue
{
    /**
     * The columns of the item import file, in template order.
     *
     * @var list<string>
     */
    public const IMPORT_COLUMNS = [
        'name', 'form', 'unit', 'unit_content', 'pack_unit', 'pack_size', 'category', 'billing_type', 'cash_price', 'reorder_level', 'sku',
        'store', 'quantity', 'batch_number', 'expiry_date', 'unit_cost',
    ];

    public function __construct(private StockLedger $stock) {}

    /**
     * Unit and pack fields left out are filled from the dosage form.
     *
     * @param  array{name: string, category: string, unit?: ?string, form?: ?string, unit_content?: ?string, pack_unit?: ?string, pack_size?: ?int, billing_type?: ?string, cash_price?: ?int, reorder_level?: ?int, sku?: ?string}  $attributes
     */
    public function create(int $facilityId, array $attributes): Item
    {
        $sku = filled($attributes['sku'] ?? null)
            ? Str::upper(trim($attributes['sku']))
            : $this->nextSku($facilityId, $attributes['name']);

        if (Item::query()->where('facility_id', $facilityId)->where('sku', $sku)->exists()) {
            throw ValidationException::withMessages([
                'sku' => "Another item already uses the code {$sku}.",
            ]);
        }

        $chosenForm = filled($attributes['form'] ?? null);
        $form = $chosenForm
            ? DosageForm::from($attributes['form'])
            : DosageForm::guess($attributes['category'], filled($attributes['unit'] ?? null) ? Str::lower(trim($attributes['unit'])) : null);
        $defaults = $chosenForm || blank($attributes['unit'] ?? null)
            ? $form->defaults()
            : ['unit' => null, 'unit_content' => null, 'pack_unit' => null, 'pack_size' => 1];
        $unit = filled($attributes['unit'] ?? null) ? trim($attributes['unit']) : ($defaults['unit'] ?? 'unit');
        $packUnit = array_key_exists('pack_unit', $attributes) ? $attributes['pack_unit'] : $defaults['pack_unit'];
        $packSize = array_key_exists('pack_size', $attributes) && filled($attributes['pack_size']) ? (int) $attributes['pack_size'] : $defaults['pack_size'];

        return Item::query()->create([
            'facility_id' => $facilityId,
            'sku' => $sku,
            'name' => trim($attributes['name']),
            'category' => $attributes['category'],
            'billing_type' => $attributes['billing_type'] ?? $this->defaultBillingType($attributes['category'])->value,
            'form' => $form,
            'unit' => $unit,
            'unit_content' => array_key_exists('unit_content', $attributes) ? (filled($attributes['unit_content']) ? trim($attributes['unit_content']) : null) : $defaults['unit_content'],
            'pack_unit' => filled($packUnit) && $packSize > 1 ? trim($packUnit) : null,
            'pack_size' => filled($packUnit) && $packSize > 1 ? $packSize : 1,
            'cash_price' => (int) ($attributes['cash_price'] ?? 0),
            'reorder_level' => (int) ($attributes['reorder_level'] ?? 0),
            'is_active' => true,
        ]);
    }

    /**
     * Change an item's details. The dispensing unit is fixed once stock has moved, because every
     * balance and movement is counted in it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Item $item, array $attributes): Item
    {
        if (isset($attributes['unit']) && trim($attributes['unit']) !== $item->unit && $item->batches()->exists()) {
            throw ValidationException::withMessages([
                'unit' => "Stock of {$item->name} is already counted in {$item->unit}s. Change the pack instead, or add a new item.",
            ]);
        }

        $packSize = (int) ($attributes['pack_size'] ?? 1);
        $packUnit = $attributes['pack_unit'] ?? null;

        $item->update([
            'name' => trim($attributes['name']),
            'form' => $attributes['form'],
            'unit' => trim($attributes['unit']),
            'unit_content' => filled($attributes['unit_content'] ?? null) ? trim($attributes['unit_content']) : null,
            'pack_unit' => filled($packUnit) && $packSize > 1 ? trim($packUnit) : null,
            'pack_size' => filled($packUnit) && $packSize > 1 ? $packSize : 1,
            'cash_price' => (int) $attributes['cash_price'],
            'reorder_level' => (int) $attributes['reorder_level'],
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ]);

        return $item;
    }

    /**
     * Put stock on a shelf without a purchase order: an opening balance, a direct delivery, or a donation.
     */
    public function addStock(
        StockLocation $location,
        Item $item,
        int $quantity,
        StockReason $reason,
        ?string $batchNumber = null,
        ?Carbon $expiry = null,
        int $unitCost = 0,
        ?string $notes = null,
    ): StockBatch {
        if (! in_array($reason, [StockReason::Opening, StockReason::Purchase, StockReason::Donation], true)) {
            throw ValidationException::withMessages([
                'reason' => 'Choose an opening balance, a delivery, or a donation.',
            ]);
        }

        if ($location->facility_id !== $item->facility_id) {
            throw ValidationException::withMessages([
                'item_id' => 'That item does not belong to this facility.',
            ]);
        }

        return DB::transaction(fn (): StockBatch => $this->stock->receive(
            $item,
            $location,
            filled($batchNumber) ? trim($batchNumber) : Str::upper($reason->value).'-'.now()->format('Ymd'),
            $expiry,
            $quantity,
            $unitCost,
            $reason,
            null,
            null,
            null,
            $notes,
        ));
    }

    /**
     * Add the starter list of common medicines and supplies. Items already on the list are skipped.
     *
     * @return int The number of items added.
     */
    public function loadStarterList(int $facilityId): int
    {
        $existing = Item::query()->where('facility_id', $facilityId)->pluck('sku')->all();
        $added = 0;

        foreach (self::starterItems() as $row) {
            if (in_array($row['sku'], $existing, true)) {
                continue;
            }

            $this->create($facilityId, $row);
            $added++;
        }

        return $added;
    }

    /**
     * Create items and opening stock from a CSV file. The whole file is rejected if any row is wrong,
     * so a corrected file can be uploaded again without making duplicates.
     *
     * @return array{created: int, stocked: int}
     */
    public function import(int $facilityId, UploadedFile $file): array
    {
        $rows = $this->readCsv($file);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'The file has no rows under the heading row.',
            ]);
        }

        $stores = StockLocation::query()->where('facility_id', $facilityId)->where('is_active', true)->get();
        $main = $stores->firstWhere('kind', 'main');
        $errors = [];

        foreach ($rows as $line => $row) {
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:150'],
                'form' => ['nullable', Rule::enum(DosageForm::class)],
                'unit' => ['required_without:form', 'nullable', 'string', 'max:30'],
                'unit_content' => ['nullable', 'string', 'max:30'],
                'pack_unit' => ['nullable', 'string', 'max:30'],
                'pack_size' => ['nullable', 'integer', 'min:1'],
                'category' => ['nullable', Rule::in(['medicine', 'consumable', 'reagent'])],
                'billing_type' => ['nullable', Rule::enum(BillingType::class)],
                'cash_price' => ['nullable', 'integer', 'min:0'],
                'reorder_level' => ['nullable', 'integer', 'min:0'],
                'sku' => ['nullable', 'string', 'max:30'],
                'store' => ['nullable', 'string', Rule::in($stores->pluck('name')->all())],
                'quantity' => ['nullable', 'integer', 'min:0'],
                'batch_number' => ['nullable', 'string', 'max:40'],
                'expiry_date' => ['nullable', 'date'],
                'unit_cost' => ['nullable', 'integer', 'min:0'],
            ], [
                'store.in' => 'The store must be one of: '.$stores->pluck('name')->join(', ').'.',
                'unit.required_without' => 'Give either a unit (such as tablet or bottle) or a form (such as tablet or oral_liquid).',
            ]);

            foreach ($validator->errors()->all() as $message) {
                $errors[] = "Row {$line}: {$message}";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => array_slice($errors, 0, 20)]);
        }

        return DB::transaction(function () use ($facilityId, $rows, $stores, $main): array {
            $created = 0;
            $stocked = 0;

            foreach ($rows as $row) {
                $item = Item::query()
                    ->where('facility_id', $facilityId)
                    ->where(function ($query) use ($row): void {
                        $query->whereRaw('LOWER(name) = ?', [Str::lower(trim($row['name']))]);

                        if (filled($row['sku'] ?? null)) {
                            $query->orWhere('sku', Str::upper(trim($row['sku'])));
                        }
                    })
                    ->first();

                if (! $item) {
                    $item = $this->create($facilityId, array_filter([
                        'name' => $row['name'],
                        'form' => $row['form'] ?: null,
                        'unit' => $row['unit'] ?: null,
                        'unit_content' => $row['unit_content'] ?: null,
                        'pack_unit' => $row['pack_unit'] ?: null,
                        'pack_size' => $row['pack_size'] ?: null,
                        'category' => $row['category'] ?: 'medicine',
                        'billing_type' => $row['billing_type'] ?: null,
                        'cash_price' => (int) ($row['cash_price'] ?: 0),
                        'reorder_level' => (int) ($row['reorder_level'] ?: 0),
                        'sku' => $row['sku'] ?: null,
                    ], fn ($value): bool => $value !== null));
                    $created++;
                }

                if ((int) ($row['quantity'] ?: 0) > 0) {
                    $store = filled($row['store'] ?? null) ? $stores->firstWhere('name', $row['store']) : $main;

                    if (! $store) {
                        throw ValidationException::withMessages([
                            'file' => 'This facility has no main store. Name a store in the store column.',
                        ]);
                    }

                    $this->addStock(
                        $store,
                        $item,
                        (int) $row['quantity'],
                        StockReason::Opening,
                        $row['batch_number'] ?: null,
                        filled($row['expiry_date'] ?? null) ? Carbon::parse($row['expiry_date']) : null,
                        (int) ($row['unit_cost'] ?: 0),
                        'Imported opening stock',
                    );
                    $stocked++;
                }
            }

            return ['created' => $created, 'stocked' => $stocked];
        });
    }

    /**
     * Common items with typical packs. Pack sizes vary by supplier, so facilities should check them.
     *
     * @return list<array{sku: string, name: string, form: string, unit: string, unit_content: ?string, pack_unit: ?string, pack_size: int, category: string, billing_type: string, cash_price: int, reorder_level: int}>
     */
    public static function starterItems(): array
    {
        $medicine = BillingType::BillableMedicine->value;
        $consumable = BillingType::BillableConsumable->value;
        $stock = BillingType::NonBillable->value;

        // sku, name, form, unit, unit content, pack unit, pack size, category, billing, cash price per unit, reorder level in units
        $rows = [
            ['PARA500', 'Paracetamol 500mg', 'tablet', 'tablet', null, 'tin', 1000, 'medicine', $medicine, 500, 400],
            ['PARASYR', 'Paracetamol 120mg/5ml syrup', 'oral_liquid', 'bottle', '100 ml', 'carton', 50, 'medicine', $medicine, 3000, 20],
            ['AMOX500', 'Amoxicillin 500mg', 'capsule', 'capsule', null, 'tin', 500, 'medicine', $medicine, 800, 200],
            ['AMOXSUS', 'Amoxicillin 125mg/5ml suspension', 'oral_liquid', 'bottle', '100 ml', 'carton', 50, 'medicine', $medicine, 4000, 20],
            ['AL20', 'Artemether-lumefantrine 20/120mg', 'tablet', 'tablet', null, 'box', 720, 'medicine', $medicine, 1500, 150],
            ['ARTSU60', 'Artesunate injection 60mg', 'injection', 'vial', null, 'box', 10, 'medicine', $medicine, 12000, 20],
            ['ORS', 'Oral rehydration salts', 'powder', 'sachet', null, 'box', 100, 'medicine', $medicine, 500, 100],
            ['ZINC20', 'Zinc sulphate 20mg', 'tablet', 'tablet', null, 'tin', 1000, 'medicine', $medicine, 300, 100],
            ['MET400', 'Metronidazole 400mg', 'tablet', 'tablet', null, 'tin', 1000, 'medicine', $medicine, 400, 120],
            ['IBU400', 'Ibuprofen 400mg', 'tablet', 'tablet', null, 'tin', 1000, 'medicine', $medicine, 600, 80],
            ['CIP500', 'Ciprofloxacin 500mg', 'tablet', 'tablet', null, 'box', 100, 'medicine', $medicine, 1000, 60],
            ['ALB400', 'Albendazole 400mg', 'tablet', 'tablet', null, 'box', 100, 'medicine', $medicine, 1000, 40],
            ['COT480', 'Cotrimoxazole 480mg', 'tablet', 'tablet', null, 'tin', 1000, 'medicine', $medicine, 300, 100],
            ['DIC50', 'Diclofenac 50mg', 'tablet', 'tablet', null, 'tin', 1000, 'medicine', $medicine, 400, 50],
            ['FEFOL', 'Ferrous sulphate + folic acid', 'tablet', 'tablet', null, 'tin', 1000, 'medicine', $medicine, 200, 200],
            ['MAGTRI', 'Magnesium trisilicate mixture', 'bulk_liquid', 'ml', null, 'can', 5000, 'medicine', $medicine, 20, 1000],
            ['OXY10', 'Oxytocin 10 IU', 'injection', 'ampoule', null, 'box', 10, 'medicine', $medicine, 2500, 20],
            ['GENT80', 'Gentamicin 80mg/2ml', 'injection', 'ampoule', null, 'box', 100, 'medicine', $medicine, 1500, 20],
            ['CEFTR1G', 'Ceftriaxone 1g injection', 'injection', 'vial', null, 'box', 10, 'medicine', $medicine, 5000, 30],
            ['NS500', 'Sodium chloride 0.9%', 'infusion', 'bottle', '500 ml', 'carton', 20, 'medicine', $medicine, 5000, 40],
            ['RL500', "Ringer's lactate", 'infusion', 'bottle', '500 ml', 'carton', 20, 'medicine', $medicine, 5000, 30],
            ['D5W', 'Dextrose 5%', 'infusion', 'bottle', '500 ml', 'carton', 20, 'medicine', $medicine, 4500, 24],
            ['TETEYE', 'Tetracycline 1% eye ointment', 'topical', 'tube', '5 g', 'box', 50, 'medicine', $medicine, 2000, 10],
            ['CLOTRI', 'Clotrimazole 1% cream', 'topical', 'tube', '20 g', 'box', 12, 'medicine', $medicine, 3500, 10],
            ['GLOVE', 'Examination gloves', 'consumable', 'pair', null, 'box', 50, 'consumable', $stock, 0, 80],
            ['SGLOVE', 'Surgical gloves', 'consumable', 'pair', null, 'box', 50, 'consumable', $stock, 0, 20],
            ['CANN18', 'Cannula 18G', 'consumable', 'piece', null, 'box', 50, 'consumable', $consumable, 2000, 30],
            ['CANN22', 'Cannula 22G', 'consumable', 'piece', null, 'box', 50, 'consumable', $consumable, 2000, 20],
            ['GIVSET', 'Giving set', 'consumable', 'piece', null, 'box', 25, 'consumable', $consumable, 1500, 30],
            ['SYR5', 'Syringe 5ml', 'consumable', 'piece', null, 'box', 100, 'consumable', $consumable, 300, 80],
            ['SYR10', 'Syringe 10ml', 'consumable', 'piece', null, 'box', 100, 'consumable', $consumable, 400, 40],
            ['COTTON', 'Cotton wool', 'consumable', 'roll', '500 g', null, 1, 'consumable', $stock, 0, 12],
            ['GAUZE', 'Gauze swabs', 'consumable', 'packet', null, 'carton', 50, 'consumable', $stock, 0, 20],
            ['SPIRIT', 'Surgical spirit', 'consumable', 'bottle', '100 ml', 'carton', 24, 'consumable', $stock, 0, 12],
            ['SUTURE', 'Vicryl 2-0 suture', 'consumable', 'piece', null, 'box', 12, 'consumable', $consumable, 8000, 10],
            ['RDT-KIT', 'Malaria RDT', 'reagent', 'test', null, 'kit', 25, 'reagent', $stock, 0, 40],
            ['GLUSTR', 'Glucose test strips', 'reagent', 'strip', null, 'tub', 50, 'reagent', $stock, 0, 25],
            ['UDIP', 'Urine dipsticks', 'reagent', 'strip', null, 'tub', 100, 'reagent', $stock, 0, 20],
            ['EDTA', 'EDTA blood tube', 'reagent', 'piece', null, 'box', 100, 'reagent', $stock, 0, 30],
        ];

        return array_map(fn (array $row): array => [
            'sku' => $row[0],
            'name' => $row[1],
            'form' => $row[2],
            'unit' => $row[3],
            'unit_content' => $row[4],
            'pack_unit' => $row[5],
            'pack_size' => $row[6],
            'category' => $row[7],
            'billing_type' => $row[8],
            'cash_price' => $row[9],
            'reorder_level' => $row[10],
        ], $rows);
    }

    private function defaultBillingType(string $category): BillingType
    {
        return match ($category) {
            'consumable' => BillingType::BillableConsumable,
            'reagent' => BillingType::NonBillable,
            default => BillingType::BillableMedicine,
        };
    }

    private function nextSku(int $facilityId, string $name): string
    {
        $base = Str::of($name)->ascii()->upper()->replaceMatches('/[^A-Z0-9]+/', '')->limit(8, '')->value() ?: 'ITEM';
        $sku = $base;
        $suffix = 2;

        while (Item::query()->where('facility_id', $facilityId)->where('sku', $sku)->exists()) {
            $sku = $base.'-'.$suffix++;
        }

        return $sku;
    }

    /**
     * @return array<int, array<string, string>> Rows keyed by their line number in the file.
     */
    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            throw ValidationException::withMessages(['file' => 'The file is empty.']);
        }

        $header = array_map(fn ($column): string => Str::snake(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $column))), $header);
        $missing = array_diff(['name'], $header);

        if ($missing !== []) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => 'The heading row needs these columns: '.implode(', ', $missing).'. Download the template to see the layout.',
            ]);
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            if (count(array_filter($values, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_fill_keys(self::IMPORT_COLUMNS, '');

            foreach ($header as $index => $column) {
                if (array_key_exists($column, $row)) {
                    $row[$column] = trim((string) ($values[$index] ?? ''));
                }
            }

            $rows[$line] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
