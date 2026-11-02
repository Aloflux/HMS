<?php

use App\Enums\Role;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\Membership;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'facility_code' => membershipOf($user)->facility->code,
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'facility_code' => membershipOf($user)->facility->code,
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('each facility password opens only its own facility', function () {
    $user = User::factory()->create(['role' => Role::Staff, 'permissions' => ['dashboard.view']]);
    $first = membershipOf($user);
    $second = Membership::factory()->create(['user_id' => $user->id, 'role' => Role::Staff, 'permissions' => ['dashboard.view'], 'password' => 'second-facility-password']);

    $this->post('/login', ['facility_code' => $first->facility->code, 'email' => $user->email, 'password' => 'second-facility-password']);
    $this->assertGuest();

    $this->post('/login', ['facility_code' => $second->facility->code, 'email' => $user->email, 'password' => 'second-facility-password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertOk()->assertSee($second->facility->name);
});

test('a wrong facility code gets the same answer as a wrong password', function () {
    $user = User::factory()->create();
    $otherFacility = User::factory()->create();

    $wrongCode = $this->post('/login', ['facility_code' => membershipOf($otherFacility)->facility->code, 'email' => $user->email, 'password' => 'password']);
    $wrongCode->assertSessionHasErrors(['email' => trans('auth.failed')]);
    $this->assertGuest();

    $unknownCode = $this->post('/login', ['facility_code' => 'NOPE', 'email' => $user->email, 'password' => 'password']);
    $unknownCode->assertSessionHasErrors(['email' => trans('auth.failed')]);
    $this->assertGuest();
});

test('facility staff cannot sign in without a facility code', function () {
    $user = User::factory()->create(['role' => Role::Staff, 'permissions' => ['dashboard.view']]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertGuest();
});

test('the facility code is remembered on the device after signing in', function () {
    $user = User::factory()->create();
    $code = membershipOf($user)->facility->code;

    $this->post('/login', ['facility_code' => $code, 'email' => $user->email, 'password' => 'password'])
        ->assertCookie(AuthenticatedSessionController::FACILITY_COOKIE, $code);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('the sign-in page names the facility for a code', function () {
    [$facility] = provisionHospital();

    $this->getJson(route('login.facility', ['code' => strtolower($facility->code)]))
        ->assertOk()
        ->assertExactJson(['found' => true, 'name' => $facility->name, 'district' => $facility->district]);
    $this->getJson(route('login.facility', ['code' => 'NOPE']))->assertNotFound();

    $facility->update(['is_active' => false]);
    $this->getJson(route('login.facility', ['code' => $facility->code]))->assertNotFound();
});

test('a remembered facility code is named on the sign-in page', function () {
    [$facility] = provisionHospital();

    $this->withCookie(AuthenticatedSessionController::FACILITY_COOKIE, $facility->code)
        ->get('/login')
        ->assertOk()
        ->assertSee($facility->name);
});

test('a facility administrator signs in without a facility code', function () {
    [$facility, $admin] = provisionHospital();

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);
    $this->get(route('dashboard'))->assertOk()->assertSee('Code '.$facility->code);
});

test('a facility administrator still needs the right password without a code', function () {
    [, $admin] = provisionHospital();

    $this->post('/login', ['email' => $admin->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);

    $this->assertGuest();
});
