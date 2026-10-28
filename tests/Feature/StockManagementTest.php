<?php

use App\Authorization\RolePermissions;
use App\Enums\BillingType;
use App\Enums\Role;
use App\Enums\StockReason;
use App\Models\Facility;
use App\Models\Item;
use App\Models\Requisition;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\EncounterService;
use App\Services\ProcurementService;
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

test('stores staff add an item with opening stock and a generated code', function () {
    [$facility] = provisionHospital();
    $keeper = userWithRole($facility, Role::PharmacyStores);
    $pharmacy = storeOfKind($facility, 'pharmacy');

    $this->actingAs($keeper)
        ->post(route('inventory.items.store'), [
            'name' => 'Ceftriaxone 1g',
            'unit' => 'vial',
            'category' => 'medicine',
            'billing_type' => 'billable_medicine',
            'cash_price' => 5000,
            'reorder_level' => 10,
            'opening_quantity' => 40,
            'stock_location_id' => $pharmacy->id,
            'expiry_date' => now()->addYear()->toDateString(),
            'unit_cost' => 3000,
        ])
        ->assertSessionHasNoErrors();

    $item = Item::query()->where('facility_id', $facility->id)->sole();
    expect($item->sku)->toBe('CEFTRIAX')
        ->and(StockBatch::query()->where('item_id', $item->id)->where('stock_location_id', $pharmacy->id)->value('quantity'))->toBe(40)
        ->and(StockMovement::query()->where('item_id', $item->id)->sole()->reason)->toBe(StockReason::Opening);
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
        ->assertSessionHasErrors(['file' => 'Row 3: The unit field is required.']);

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
        ->assertSeeInOrder(['Running low on the pharmacy shelf', 'Amoxicillin 500mg', '30 left, reorder at 50', '500 in the main store'])
        ->assertSee('Restock the pharmacy');
});
