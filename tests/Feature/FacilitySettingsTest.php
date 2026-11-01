<?php

use App\Enums\Role;
use App\Models\Bill;
use App\Models\User;

/**
 * @return array<string, string>
 */
function facilitySettings(array $overrides = []): array
{
    return [
        'name' => 'Test Hospital',
        'kind' => 'hospital',
        'district' => 'Kampala',
        'currency' => 'KES',
        ...$overrides,
    ];
}

test('an administrator can change the collection currency', function () {
    [$facility, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->get(route('admin.facility.edit'))
        ->assertOk()
        ->assertSee('US dollar (USD)');

    $this->actingAs($admin)
        ->patch(route('admin.facility.update'), facilitySettings(['currency' => 'USD']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($facility->fresh()->currency)->toBe('USD');

    $this->actingAs($admin)
        ->get(route('admin.catalogue.index'))
        ->assertSee('Cash price (USD)')
        ->assertDontSee('UGX');
});

test('the currency must be one the platform supports', function () {
    [$facility, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->patch(route('admin.facility.update'), facilitySettings(['currency' => 'XYZ']))
        ->assertSessionHasErrors('currency');

    expect($facility->fresh()->currency)->toBe('UGX');
});

test('new bills use the facility currency and keep it after the currency changes', function () {
    [$facility, $admin] = provisionHospital();
    $facility->update(['currency' => 'TZS']);
    $visit = openCashVisit($facility, $admin);

    $this->actingAs($admin)->patch(route('admin.facility.update'), facilitySettings(['currency' => 'RWF']));

    expect(Bill::query()->where('visit_id', $visit->id)->value('currency'))->toBe('TZS');

    $this->actingAs($admin)
        ->get(route('visits.show', $visit))
        ->assertSee('TZS 0');
});

test('staff without administration access cannot change facility settings', function () {
    [$facility] = provisionHospital();
    $cashier = User::factory()->create(['facility_id' => $facility->id, 'role' => Role::FrontOffice]);

    $this->actingAs($cashier)
        ->patch(route('admin.facility.update'), facilitySettings())
        ->assertForbidden();

    expect($facility->fresh()->currency)->toBe('UGX');
});
