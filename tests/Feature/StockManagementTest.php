<?php

use App\Authorization\RolePermissions;
use App\Enums\BillingType;
use App\Enums\Role;
use App\Enums\StockReason;
use App\Models\Facility;
use App\Models\Item;
use App\Models\PurchaseOrderLine;
use App\Models\Requisition;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\EncounterService;
use App\Services\ProcurementService;
use App\Services\StockReport;
use App\Support\FacilityContext;
use Illuminate\Http\UploadedFile;

function stockItem(Facility $facility, string $name = 'Paracetamol 500mg', int $reorderLevel = 0): Item
{
    return Item::query()->create([
        'facility_id' => $facility->id,
        'sku' => strtoupper(substr(preg_replace('/\W/', '', $name), 0, 8)),
        'name' => $name,
        'category' => 'medicine',
        'billing_type' => BillingType::BillableMedicine,
        'unit' => 'tablet',
        'cash_price' => 500,
        'reorder_level' => $reorderLevel,
        'is_active' => true,
    ]);
}

function storeOfKind(Facility $facility, string $kind): StockLocation
{
    return StockLocation::query()->where('facility_id', $facility->id)->where('kind', $kind)->firstOrFail();
}

function shelve(Item $item, StockLocation $location, int $quantity, string $batch = 'B1', int $unitCost = 200): StockBatch
{
    return StockBatch::query()->create([
        'facility_id' => $item->facility_id,
        'item_id' => $item->id,
        'stock_location_id' => $location->id,
        'batch_number' => $batch,
        'expiry_date' => now()->addYear()->toDateString(),
        'unit_cost' => $unitCost,
        'quantity' => $quantity,
    ]);
}

function userWithRole(Facility $facility, Role $role, string $name = 'Staff Member'): User
{
    return User::factory()->create([
        'facility_id' => $facility->id,
        'role' => $role,
        'name' => $name,
    ]);
}

function dispenseTo(Facility $facility, User $prescriber, User $pharmacist, Item $item, int $quantity): void
{
    $visit = openCashVisit($facility, $prescriber);
    FacilityContext::set($facility->id);
    $prescription = app(EncounterService::class)->prescribe($visit, $prescriber, [[
        'item_id' => $item->id,
        'dose' => '1g',
        'frequency' => 'TDS',
        'duration_days' => 3,
        'quantity' => $quantity,
    ]]);
    FacilityContext::forget();

    test()->actingAs($pharmacist)
        ->post(route('pharmacy.dispense', $prescription->items()->first()), ['quantity' => $quantity])
        ->assertSessionHasNoErrors();
}

test('a dispense records the pharmacy department and the pharmacist on the movement', function () {
    [$facility, $admin] = provisionHospital();
    $item = stockItem($facility);
    $pharmacy = storeOfKind($facility, 'pharmacy');
    shelve($item, $pharmacy, 10);
    $pharmacist = userWithRole($facility, Role::PharmacyStores);

    dispenseTo($facility, $admin, $pharmacist, $item, 4);

    $movement = StockMovement::query()->where('reason', StockReason::Dispense)->sole();
    expect($movement->quantity)->toBe(-4)
        ->and($movement->department_id)->toBe($pharmacy->department_id)
        ->and($movement->performed_by)->toBe($pharmacist->id);
});

test('a requisition issues what the main store has and leaves the rest open', function () {
    [$facility, $admin] = provisionHospital();
    $item = stockItem($facility);
    $main = storeOfKind($facility, 'main');
    $pharmacy = storeOfKind($facility, 'pharmacy');
    shelve($item, $main, 5);

    $this->actingAs($admin)
        ->post(route('inventory.requisition'), [
            'stock_location_id' => $pharmacy->id,
            'lines' => [['item_id' => $item->id, 'quantity' => 8]],
        ])
        ->assertSessionHasNoErrors();
    $requisition = Requisition::query()->sole();

    $this->actingAs($admin)->post(route('inventory.issue', $requisition))->assertSessionHasNoErrors();

    expect($requisition->fresh()->status)->toBe('partial')
        ->and($requisition->lines()->sole()->quantity_issued)->toBe(5)
        ->and(StockBatch::query()->where('stock_location_id', $pharmacy->id)->sum('quantity'))->toEqual(5);

    shelve($item, $main, 10, 'B2');
    $this->actingAs($admin)->post(route('inventory.issue', $requisition))->assertSessionHasNoErrors();

    expect($requisition->fresh()->status)->toBe('issued')
        ->and($requisition->lines()->sole()->quantity_issued)->toBe(8)
        ->and(StockBatch::query()->where('stock_location_id', $main->id)->sum('quantity'))->toEqual(7);
});

test('a requisition is not issued when the main store has none of it', function () {
    [$facility, $admin] = provisionHospital();
    $item = stockItem($facility);
    FacilityContext::set($facility->id);
    $requisition = app(ProcurementService::class)->requestStock(storeOfKind($facility, 'pharmacy'), $admin, [
        ['item_id' => $item->id, 'quantity' => 3],
    ]);
    FacilityContext::forget();

    $this->actingAs($admin)
        ->post(route('inventory.issue', $requisition))
        ->assertSessionHasErrors(['requisition' => 'The main store has none of the requested items in stock.']);

    expect($requisition->fresh()->status)->toBe('submitted');
});

test('a requisition can ask for several items but not for the main store', function () {
    [$facility, $admin] = provisionHospital();
    $paracetamol = stockItem($facility);
    $amoxicillin = stockItem($facility, 'Amoxicillin 500mg');

    $this->actingAs($admin)
        ->post(route('inventory.requisition'), [
            'stock_location_id' => storeOfKind($facility, 'pharmacy')->id,
            'lines' => [
                ['item_id' => $paracetamol->id, 'quantity' => 100],
                ['item_id' => $amoxicillin->id, 'quantity' => 50],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect(Requisition::query()->sole()->lines()->pluck('quantity_requested', 'item_id')->all())
        ->toBe([$paracetamol->id => 100, $amoxicillin->id => 50]);

    $this->actingAs($admin)
        ->post(route('inventory.requisition'), [
            'stock_location_id' => storeOfKind($facility, 'main')->id,
            'lines' => [['item_id' => $paracetamol->id, 'quantity' => 10]],
        ])
        ->assertSessionHasErrors(['stock_location_id' => 'The main store restocks through purchase orders, not requisitions.']);
});

test('a store returns unused stock to the main store with the batch', function () {
    [$facility, $admin] = provisionHospital();
    $item = stockItem($facility);
    $pharmacy = storeOfKind($facility, 'pharmacy');
    $main = storeOfKind($facility, 'main');
    shelve($item, $pharmacy, 10, 'PH-1');

    $this->actingAs($admin)
        ->post(route('inventory.return'), [
            'stock_location_id' => $pharmacy->id,
            'item_id' => $item->id,
            'quantity' => 4,
            'notes' => 'Overstocked',
        ])
        ->assertSessionHasNoErrors();

    expect(StockBatch::query()->where('stock_location_id', $main->id)->where('batch_number', 'PH-1')->value('quantity'))->toBe(4)
        ->and(StockBatch::query()->where('stock_location_id', $pharmacy->id)->value('quantity'))->toBe(6)
        ->and(StockMovement::query()->where('reason', StockReason::Return)->pluck('quantity')->sort()->values()->all())->toBe([-4, 4]);
});

test('stock levels show each store and flag an item at its reorder level', function () {
    [$facility] = provisionHospital();
    $item = stockItem($facility, 'Amoxicillin 500mg', reorderLevel: 20);
    shelve($item, storeOfKind($facility, 'main'), 12);
    shelve($item, storeOfKind($facility, 'pharmacy'), 6);

    $dispenser = User::factory()->create([
        'facility_id' => $facility->id,
        'role' => Role::Staff,
        'permissions' => RolePermissions::permissionsForAreas(['pharmacy']),
    ]);

    $this->actingAs($dispenser)
        ->get(route('stock.levels'))
        ->assertOk()
        ->assertSeeInOrder(['Amoxicillin 500mg', '12', '6', '18', '20'])
        ->assertSee('Low');
});

test('front office cannot open stock levels', function () {
    [$facility] = provisionHospital();

    $this->actingAs(userWithRole($facility, Role::FrontOffice))
        ->get(route('stock.levels'))
        ->assertForbidden();
});

test('the stock card lists who moved stock with the balance after each movement', function () {
    [$facility, $admin] = provisionHospital();
    $item = stockItem($facility);
    shelve($item, storeOfKind($facility, 'pharmacy'), 10);
    $pharmacist = userWithRole($facility, Role::PharmacyStores, 'Grace Pharmacist');
    dispenseTo($facility, $admin, $pharmacist, $item, 3);

    $this->actingAs($admin)
        ->get(route('stock.levels.item', $item))
        ->assertOk()
        ->assertSeeInOrder(['Dispensed to patient', '3', '7', 'Pharmacy', 'Okello Peter', 'Grace Pharmacist']);
});

test('another facility cannot open this facility stock card', function () {
    [$facility] = provisionHospital();
    [, $otherAdmin] = provisionHospital();
    $item = stockItem($facility);

    $this->actingAs($otherAdmin)
        ->get(route('stock.levels.item', $item))
        ->assertNotFound();
});

test('the usage report totals use and cost by department and by employee', function () {
    [$facility, $admin] = provisionHospital();
    $item = stockItem($facility);
    shelve($item, storeOfKind($facility, 'pharmacy'), 20, unitCost: 150);
    $pharmacist = userWithRole($facility, Role::PharmacyStores, 'Grace Pharmacist');
    dispenseTo($facility, $admin, $pharmacist, $item, 6);

    $this->actingAs($admin)
        ->get(route('stock.usage'))
        ->assertOk()
        ->assertSeeInOrder(['Pharmacy', 'Paracetamol 500mg', '6 tablets', 'UGX 900']);

    $this->actingAs($admin)
        ->get(route('stock.usage', ['group_by' => 'employee', 'performed_by' => $admin->id]))
        ->assertOk()
        ->assertSee('No stock was used in this period');

    $csv = $this->actingAs($admin)
        ->get(route('stock.usage.export', ['group_by' => 'employee']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('"Grace Pharmacist","Paracetamol 500mg",tablet,6,900');
});

test('stores staff add an item with its pack and opening stock counted in packs', function () {
    [$facility] = provisionHospital();
    $keeper = userWithRole($facility, Role::PharmacyStores);
    $pharmacy = storeOfKind($facility, 'pharmacy');

    $this->actingAs($keeper)
        ->post(route('inventory.items.store'), [
            'name' => 'Ceftriaxone 1g',
            'form' => 'injection',
            'unit' => 'vial',
            'pack_unit' => 'box',
            'pack_size' => 10,
            'category' => 'medicine',
            'billing_type' => 'billable_medicine',
            'cash_price' => 5000,
            'reorder_level' => 10,
            'opening_quantity' => 4,
            'count_in' => 'pack',
            'stock_location_id' => $pharmacy->id,
            'expiry_date' => now()->addYear()->toDateString(),
            'unit_cost' => 30000,
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Ceftriaxone 1g added with 40 vials (4 boxes) in stock.');

    $item = Item::query()->where('facility_id', $facility->id)->sole();
    $batch = StockBatch::query()->where('item_id', $item->id)->where('stock_location_id', $pharmacy->id)->sole();
    expect($item->sku)->toBe('CEFTRIAX')
        ->and($item->packLabel())->toBe('box of 10 vials')
        ->and($batch->quantity)->toBe(40)
        ->and($batch->unit_cost)->toBe(3000)
        ->and(StockMovement::query()->where('item_id', $item->id)->sole()->reason)->toBe(StockReason::Opening);
});

test('a syrup is ordered and received in cartons and stocked in bottles', function () {
    [$facility, $admin] = provisionHospital();
    $syrup = Item::query()->create([
        'facility_id' => $facility->id,
        'sku' => 'AMOXSUS',
        'name' => 'Amoxicillin 125mg/5ml suspension',
        'category' => 'medicine',
        'billing_type' => BillingType::BillableMedicine,
        'form' => 'oral_liquid',
        'unit' => 'bottle',
        'unit_content' => '100 ml',
        'pack_unit' => 'carton',
        'pack_size' => 50,
        'cash_price' => 4000,
        'reorder_level' => 20,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('inventory.order'), [
            'supplier_name' => 'Joint Medical Stores',
            'item_id' => $syrup->id,
            'quantity' => 3,
            'unit_cost' => 125000,
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Purchase order sent for 150 bottles (3 cartons).');

    $line = PurchaseOrderLine::query()->sole();
    expect($line->quantity)->toBe(150)
        ->and($line->pack_size)->toBe(50)
        ->and($line->unit_cost)->toBe(2500);

    $this->actingAs($admin)
        ->post(route('inventory.receive', $line->purchase_order_id), [
            'purchase_order_line_id' => $line->id,
            'batch_number' => 'AX-1',
            'quantity' => 2,
            'count_in' => 'pack',
        ])
        ->assertSessionHasNoErrors();

    expect(StockBatch::query()->where('batch_number', 'AX-1')->value('quantity'))->toBe(100)
        ->and($line->fresh()->received_quantity)->toBe(100);
});

test('a requisition counted in packs moves whole packs of units', function () {
    [$facility, $admin] = provisionHospital();
    $tablets = stockItem($facility);
    $tablets->update(['form' => 'tablet', 'pack_unit' => 'tin', 'pack_size' => 1000]);
    $main = storeOfKind($facility, 'main');
    $pharmacy = storeOfKind($facility, 'pharmacy');
    shelve($tablets, $main, 5000);

    $this->actingAs($admin)
        ->post(route('inventory.requisition'), [
            'stock_location_id' => $pharmacy->id,
            'lines' => [['item_id' => $tablets->id, 'quantity' => 2, 'count_in' => 'pack']],
        ])
        ->assertSessionHasNoErrors();

    expect(Requisition::query()->sole()->lines()->sole()->quantity_requested)->toBe(2000);
});

test('restock suggestions round up to whole packs', function () {
    [$facility] = provisionHospital();
    $tablets = stockItem($facility, 'Paracetamol 500mg', reorderLevel: 400);
    $tablets->update(['form' => 'tablet', 'pack_unit' => 'tin', 'pack_size' => 1000]);
    $pharmacy = storeOfKind($facility, 'pharmacy');
    shelve($tablets, $pharmacy, 150);

    FacilityContext::set($facility->id);
    $suggestion = app(StockReport::class)->restockSuggestions($pharmacy)->sole();
    FacilityContext::forget();

    expect($suggestion['suggested_packs'])->toBe(1)
        ->and($suggestion['suggested'])->toBe(1000);
});

test('the dispensing instruction says whether to count, measure, or hand over whole', function (array $attributes, int $quantity, string $instruction) {
    $item = new Item([
        'name' => 'Medicine',
        'unit_content' => null,
        'pack_unit' => null,
        'pack_size' => 1,
        ...$attributes,
    ]);

    expect($item->dispensingInstruction($quantity))->toBe($instruction);
})->with([
    'tablets from a tin' => [['form' => 'tablet', 'unit' => 'tablet', 'pack_unit' => 'tin', 'pack_size' => 1000], 21, 'Count out 21 tablets from the tin'],
    'a whole syrup bottle' => [['form' => 'oral_liquid', 'unit' => 'bottle', 'unit_content' => '100 ml'], 1, 'Hand over 1 bottle (100 ml)'],
    'two syrup bottles' => [['form' => 'oral_liquid', 'unit' => 'bottle', 'unit_content' => '100 ml'], 2, 'Hand over 2 bottles (100 ml each)'],
    'a mixture from a can' => [['form' => 'bulk_liquid', 'unit' => 'ml', 'pack_unit' => 'can', 'pack_size' => 5000], 200, 'Measure out 200 ml from the can'],
]);

test('an item unit cannot change once it has stock but its pack can', function () {
    [$facility, $admin] = provisionHospital();
    $item = stockItem($facility);
    shelve($item, storeOfKind($facility, 'main'), 50);
    $details = [
        'name' => $item->name,
        'form' => 'tablet',
        'unit' => 'tablet',
        'pack_unit' => 'tin',
        'pack_size' => 1000,
        'cash_price' => 600,
        'reorder_level' => 100,
        'is_active' => 1,
    ];

    $this->actingAs($admin)
        ->patch(route('inventory.items.update', $item), [...$details, 'unit' => 'strip'])
        ->assertSessionHasErrors(['unit' => 'Stock of Paracetamol 500mg is already counted in tablets. Change the pack instead, or add a new item.']);

    $this->actingAs($admin)
        ->patch(route('inventory.items.update', $item), $details)
        ->assertSessionHasNoErrors();

    expect($item->fresh()->packLabel())->toBe('tin of 1,000 tablets')
        ->and($item->fresh()->cash_price)->toBe(600);
});

test('clinical staff cannot add items or stock', function () {
    [$facility] = provisionHospital();

    $this->actingAs(userWithRole($facility, Role::Clinical))
        ->post(route('inventory.items.store'), ['name' => 'Anything'])
        ->assertForbidden();
});

test('a delivery without a purchase order adds stock to the chosen store', function () {
    [$facility, $admin] = provisionHospital();
    $item = stockItem($facility);
    $main = storeOfKind($facility, 'main');

    $this->actingAs($admin)
        ->post(route('inventory.stock.add'), [
            'item_id' => $item->id,
            'stock_location_id' => $main->id,
            'reason' => 'donation',
            'quantity' => 250,
            'batch_number' => 'DON-1',
            'notes' => 'From district health office',
        ])
        ->assertSessionHasNoErrors();

    $movement = StockMovement::query()->sole();
    expect(StockBatch::query()->where('stock_location_id', $main->id)->where('batch_number', 'DON-1')->value('quantity'))->toBe(250)
        ->and($movement->reason)->toBe(StockReason::Donation)
        ->and($movement->notes)->toBe('From district health office');
});

test('a csv import creates items and opening stock and tops up an existing item', function () {
    [$facility, $admin] = provisionHospital();
    $existing = stockItem($facility);
    $csv = "name,unit,category,cash_price,reorder_level,store,quantity,batch_number,expiry_date,unit_cost\n"
        ."Paracetamol 500mg,tablet,medicine,500,100,Main store,300,P-9,2028-01-31,150\n"
        ."Examination gloves,pair,consumable,0,50,Pharmacy,200,,,400\n"
        ."Zinc sulphate 20mg,tablet,,300,,,,,,\n";

    $this->actingAs($admin)
        ->post(route('inventory.items.import'), [
            'file' => UploadedFile::fake()->createWithContent('items.csv', $csv),
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Import finished: 2 new items, 2 stock lines added.');

    $gloves = Item::query()->where('name', 'Examination gloves')->sole();
    expect(Item::query()->where('facility_id', $facility->id)->count())->toBe(3)
        ->and($gloves->billing_type)->toBe(BillingType::BillableConsumable)
        ->and(StockBatch::query()->where('item_id', $existing->id)->where('batch_number', 'P-9')->value('quantity'))->toBe(300)
        ->and(StockBatch::query()->where('item_id', $gloves->id)->where('stock_location_id', storeOfKind($facility, 'pharmacy')->id)->value('quantity'))->toBe(200);
});

test('a csv import with a bad row saves nothing and names the row', function () {
    [$facility, $admin] = provisionHospital();
    $csv = "name,unit,quantity,store\nAmoxicillin 500mg,capsule,100,Main store\nGauze,,10,Main store\n";

    $this->actingAs($admin)
        ->post(route('inventory.items.import'), [
            'file' => UploadedFile::fake()->createWithContent('items.csv', $csv),
        ])
        ->assertSessionHasErrors(['file' => 'Row 3: Give either a unit (such as tablet or bottle) or a form (such as tablet or oral_liquid).']);

    expect(Item::query()->where('facility_id', $facility->id)->count())->toBe(0);
});

test('the starter list adds common items once', function () {
    [$facility, $admin] = provisionHospital();

    $this->actingAs($admin)->post(route('inventory.items.starter'))->assertSessionHasNoErrors();
    $count = Item::query()->where('facility_id', $facility->id)->count();
    $this->actingAs($admin)->post(route('inventory.items.starter'))
        ->assertSessionHas('status', 'Every item on the starter list is already here.');

    expect($count)->toBeGreaterThan(20)
        ->and(Item::query()->where('facility_id', $facility->id)->count())->toBe($count);
});

test('a facility without items is pointed to the starter list on the inventory page', function () {
    [, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->get(route('inventory.index'))
        ->assertOk()
        ->assertSee('There are no items yet.')
        ->assertSee('Load the starter list');
});

test('the pharmacy warns about a shelf item at its reorder level and offers a restock', function () {
    [$facility] = provisionHospital();
    $item = stockItem($facility, 'Amoxicillin 500mg', reorderLevel: 50);
    shelve($item, storeOfKind($facility, 'pharmacy'), 30);
    shelve($item, storeOfKind($facility, 'main'), 500, 'M-1');

    $this->actingAs(userWithRole($facility, Role::PharmacyStores))
        ->get(route('pharmacy.index'))
        ->assertOk()
        ->assertSeeInOrder(['1 medicine running low on the shelf:', 'Amoxicillin 500mg', 'Review and restock'])
        ->assertSeeInOrder(['Request stock from the main store', 'Amoxicillin 500mg', '30 left, reorder at 50', '500 tablets in the main store']);
});

test('a dispense that fails validation reopens its dispense form', function () {
    [$facility, $admin] = provisionHospital();
    $item = stockItem($facility);
    shelve($item, storeOfKind($facility, 'pharmacy'), 2);
    $pharmacist = userWithRole($facility, Role::PharmacyStores);
    $visit = openCashVisit($facility, $admin);
    FacilityContext::set($facility->id);
    $line = app(EncounterService::class)->prescribe($visit, $admin, [[
        'item_id' => $item->id,
        'dose' => '1g',
        'frequency' => 'TDS',
        'duration_days' => 3,
        'quantity' => 6,
    ]])->items()->first();
    FacilityContext::forget();

    $this->actingAs($pharmacist)
        ->from(route('pharmacy.index'))
        ->post(route('pharmacy.dispense', $line), ['quantity' => 6, '_modal' => 'dispense-'.$line->id])
        ->assertSessionHasErrors('quantity');

    $this->actingAs($pharmacist)
        ->get(route('pharmacy.index'))
        ->assertOk()
        ->assertSee('show: true', false);
});

test('the pharmacy adds a direct delivery to its own shelf', function () {
    [$facility] = provisionHospital();
    $item = stockItem($facility);
    $pharmacy = storeOfKind($facility, 'pharmacy');
    $pharmacist = userWithRole($facility, Role::PharmacyStores);

    $this->actingAs($pharmacist)
        ->get(route('pharmacy.index'))
        ->assertOk()
        ->assertSee('Add stock to the pharmacy');

    $this->actingAs($pharmacist)
        ->post(route('inventory.stock.add'), [
            'item_id' => $item->id,
            'stock_location_id' => $pharmacy->id,
            'reason' => 'purchase',
            'quantity' => 60,
            'notes' => 'Delivered by Joint Medical Stores',
        ])
        ->assertSessionHasNoErrors();

    expect(StockBatch::query()->where('stock_location_id', $pharmacy->id)->sum('quantity'))->toEqual(60)
        ->and(StockMovement::query()->sole()->reason)->toBe(StockReason::Purchase);
});
