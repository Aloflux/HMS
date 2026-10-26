<?php

use App\Enums\Role;
use App\Models\Bed;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Ward;
use App\Services\EncounterService;
use App\Support\FacilityContext;

function wardNamed(string $code, int $facilityId): Ward
{
    return Ward::query()->where('facility_id', $facilityId)->where('code', $code)->firstOrFail();
}

test('administrators can add a ward with numbered beds and a ward store', function () {
    [$facility, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->post(route('admin.wards.store'), [
            'name' => 'Paediatric ward',
            'code' => 'pw',
            'department_id' => $facility->departments()->where('code', 'WF')->value('id'),
            'beds' => 3,
            'create_store' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $ward = wardNamed('PW', $facility->id);

    expect($ward->beds()->orderBy('id')->pluck('code')->all())->toBe(['Bed 1', 'Bed 2', 'Bed 3']);
    expect(StockLocation::query()->where('facility_id', $facility->id)->where('name', 'Paediatric ward store')->value('kind'))->toBe('ward');
});

test('ward codes must be unique within the facility', function () {
    [$facility, $admin] = provisionHospital();

    $this->actingAs($admin)
        ->post(route('admin.wards.store'), ['name' => 'Another female ward', 'code' => 'WF', 'beds' => 1])
        ->assertSessionHasErrors('code');

    expect(Ward::query()->where('facility_id', $facility->id)->count())->toBe(2);
});

test('adding beds continues numbering after the highest bed', function () {
    [$facility, $admin] = provisionHospital();
    $ward = wardNamed('WF', $facility->id);

    $this->actingAs($admin)
        ->post(route('admin.wards.beds.store', $ward), ['count' => 2])
        ->assertRedirect();

    expect($ward->beds()->orderBy('id')->pluck('code')->all())->toBe(['Bed 1', 'Bed 2', 'Bed 3', 'Bed 4', 'Bed 5', 'Bed 6']);
});

test('adding beds to a ward with older or custom names continues from the highest number', function () {
    [$facility, $admin] = provisionHospital();
    $ward = wardNamed('WF', $facility->id);
    $ward->beds()->orderBy('id')->get()->each(fn (Bed $bed, int $index) => $bed->update(['code' => 'WF-'.($index + 1)]));
    $ward->beds()->orderByDesc('id')->first()->update(['code' => 'Side room']);

    $this->actingAs($admin)
        ->post(route('admin.wards.beds.store', $ward), ['count' => 1])
        ->assertRedirect();

    expect($ward->beds()->orderByDesc('id')->value('code'))->toBe('Bed 4');
});

test('administrators can rename a bed but not to a name already used in the same ward', function () {
    [$facility, $admin] = provisionHospital();
    $ward = wardNamed('WF', $facility->id);
    [$first, $second] = $ward->beds()->orderBy('id')->take(2)->get()->all();

    $this->actingAs($admin)
        ->patch(route('admin.beds.rename', $first), ['label' => 'Cot 3'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($first->fresh()->code)->toBe('Cot 3');

    $this->actingAs($admin)
        ->patch(route('admin.beds.rename', $second), ['label' => 'Cot 3'])
        ->assertSessionHasErrors('label');

    expect($second->fresh()->code)->toBe('Bed 2');

    $otherWardBed = wardNamed('WM', $facility->id)->beds()->orderBy('id')->first();

    $this->actingAs($admin)
        ->patch(route('admin.beds.rename', $otherWardBed), ['label' => 'Cot 3'])
        ->assertSessionHasNoErrors();
});

test('administrators can rename a ward', function () {
    [$facility, $admin] = provisionHospital();
    $ward = wardNamed('WM', $facility->id);

    $this->actingAs($admin)
        ->patch(route('admin.wards.update', $ward), ['name' => 'Surgical ward'])
        ->assertRedirect();

    expect($ward->fresh()->name)->toBe('Surgical ward');
});

test('a closed bed leaves capacity and cannot take an admission until it is reopened', function () {
    [$facility, $admin] = provisionHospital();
    $bed = wardNamed('WF', $facility->id)->beds()->orderBy('id')->first();

    $this->actingAs($admin)
        ->patch(route('admin.beds.update', $bed), ['action' => 'close'])
        ->assertRedirect();

    expect($bed->fresh()->status)->toBe('closed');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('0 / 7 beds');

    $this->actingAs($admin)
        ->patch(route('admin.beds.update', $bed), ['action' => 'reopen'])
        ->assertRedirect();

    expect($bed->fresh()->status)->toBe('available');
});

test('an occupied bed cannot be closed or removed and a bed with history cannot be removed', function () {
    [$facility, $admin] = provisionHospital();
    $bed = wardNamed('WF', $facility->id)->beds()->orderBy('id')->first();
    $visit = openCashVisit($facility, $admin);
    FacilityContext::set($facility->id);
    $admission = app(EncounterService::class)->admit($visit, $admin, $bed, 'Malaria');
    FacilityContext::forget();

    $this->actingAs($admin)
        ->patch(route('admin.beds.update', $bed), ['action' => 'close'])
        ->assertSessionHasErrors('bed');

    $this->actingAs($admin)
        ->delete(route('admin.beds.destroy', $bed))
        ->assertSessionHasErrors('bed');

    $admission->update(['status' => 'discharged']);
    $bed->update(['status' => 'available']);

    $this->actingAs($admin)
        ->delete(route('admin.beds.destroy', $bed))
        ->assertSessionHasErrors('bed');

    $this->actingAs($admin)
        ->delete(route('admin.wards.destroy', $bed->ward))
        ->assertSessionHasErrors('ward');

    expect(Bed::query()->whereKey($bed->id)->exists())->toBeTrue();
});

test('unused beds and wards can be removed', function () {
    [$facility, $admin] = provisionHospital();
    $ward = wardNamed('WM', $facility->id);
    $bed = $ward->beds()->orderByDesc('id')->first();

    $this->actingAs($admin)
        ->delete(route('admin.beds.destroy', $bed))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($ward->beds()->count())->toBe(3);

    $this->actingAs($admin)
        ->delete(route('admin.wards.destroy', $ward))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Ward::query()->whereKey($ward->id)->exists())->toBeFalse();
});

test('wards from another facility cannot be changed', function () {
    [, $admin] = provisionHospital();
    [$otherFacility] = provisionHospital();
    $otherWard = wardNamed('WF', $otherFacility->id);

    $this->actingAs($admin)
        ->post(route('admin.wards.beds.store', $otherWard), ['count' => 1])
        ->assertNotFound();

    expect($otherWard->beds()->count())->toBe(4);
});

test('only administrators can manage wards', function () {
    [$facility, $admin] = provisionHospital();
    $clerk = User::factory()->create(['facility_id' => $facility->id, 'role' => Role::FrontOffice]);

    $this->actingAs($clerk)
        ->get(route('admin.wards.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.wards.index'))
        ->assertOk()
        ->assertSee('Female ward')
        ->assertSee('Bed 1');
});
