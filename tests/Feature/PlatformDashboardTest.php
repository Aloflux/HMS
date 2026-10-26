<?php

use App\Enums\Role;
use App\Models\Facility;
use App\Models\User;

function platformOperator(): User
{
    return User::factory()->create(['role' => Role::PlatformAdmin, 'facility_id' => null]);
}

test('the platform operator lands on the platform overview after signing in', function () {
    $operator = platformOperator();

    $this->post(route('login'), ['email' => $operator->email, 'password' => 'password'])
        ->assertRedirect(route('platform.dashboard'));
});

test('the overview shows platform totals and what needs attention without patient details', function () {
    [$live, $admin] = provisionHospital();
    $settingUp = Facility::factory()->pendingOnboarding()->create(['name' => 'Fresh Sign-up Clinic']);
    $orphan = Facility::factory()->create(['name' => 'Orphan Health Centre']);
    $dormant = Facility::factory()->create(['name' => 'Dormant Hospital', 'is_active' => false]);
    openCashVisit($live, $admin);

    $this->actingAs(platformOperator())
        ->get(route('platform.dashboard'))
        ->assertOk()
        ->assertSee('Platform overview')
        ->assertSee('Fresh Sign-up Clinic')
        ->assertSee('Orphan Health Centre')
        ->assertSee('No active admin')
        ->assertSee('Dormant Hospital')
        ->assertSee('Reactivate')
        ->assertSee($live->name)
        ->assertDontSee('Okello');
});

test('facility staff cannot open the platform overview', function () {
    [, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->get(route('platform.dashboard'))
        ->assertForbidden();
});

test('the facility directory filters by status and search', function () {
    Facility::factory()->create(['name' => 'Alpha Clinic', 'district' => 'Gulu']);
    Facility::factory()->pendingOnboarding()->create(['name' => 'Bravo Clinic', 'district' => 'Mbarara']);
    Facility::factory()->create(['name' => 'Charlie Hospital', 'is_active' => false]);
    $operator = platformOperator();

    $this->actingAs($operator)
        ->get(route('platform.facilities.index', ['status' => 'setup']))
        ->assertOk()
        ->assertSee('Bravo Clinic')
        ->assertDontSee('Alpha Clinic')
        ->assertDontSee('Charlie Hospital');

    $this->actingAs($operator)
        ->get(route('platform.facilities.index', ['status' => 'inactive']))
        ->assertSee('Charlie Hospital')
        ->assertDontSee('Bravo Clinic');

    $this->actingAs($operator)
        ->get(route('platform.facilities.index', ['q' => 'gulu']))
        ->assertSee('Alpha Clinic')
        ->assertDontSee('Bravo Clinic');
});

test('the provision dialog opens on request and after a failed submission', function () {
    $operator = platformOperator();

    $this->actingAs($operator)
        ->get(route('platform.facilities.index', ['provision' => 1]))
        ->assertOk()
        ->assertSee('show: true', false);

    $this->actingAs($operator)
        ->from(route('platform.facilities.index'))
        ->post(route('platform.facilities.store'), ['name' => 'Incomplete'])
        ->assertSessionHasErrors('code');

    $this->actingAs($operator)
        ->get(route('platform.facilities.index'))
        ->assertSee('show: true', false);
});

test('the operator can deactivate and reactivate a facility', function () {
    $facility = Facility::factory()->create();
    $operator = platformOperator();

    $this->actingAs($operator)
        ->patch(route('platform.facilities.update', $facility), ['is_active' => '0'])
        ->assertRedirect();

    expect($facility->fresh()->is_active)->toBeFalse();

    $this->actingAs($operator)
        ->patch(route('platform.facilities.update', $facility), ['is_active' => '1']);

    expect($facility->fresh()->is_active)->toBeTrue();
});
