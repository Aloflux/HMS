<?php

use App\Enums\Role;
use App\Models\Membership;
use App\Models\User;
use App\Notifications\MembershipPasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested for one facility', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['facility_code' => membershipOf($user)->facility->code, 'email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, MembershipPasswordReset::class);
});

test('a request that matches nobody gets the same answer and sends nothing', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['facility_code' => 'NOPE', 'email' => $user->email])
        ->assertSessionHas('status', 'If those details match an account, we have emailed a link to choose a new password.');

    Notification::assertNothingSent();
});

test('reset password screen can be rendered', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['facility_code' => membershipOf($user)->facility->code, 'email' => $user->email]);

    Notification::assertSentTo($user, MembershipPasswordReset::class, function (MembershipPasswordReset $notification) {
        $this->get(route('password.reset', ['token' => $notification->token, 'facility' => $notification->membership->facility->code]))
            ->assertStatus(200);

        return true;
    });
});

test('a reset changes only that facility\'s password', function () {
    Notification::fake();
    $user = User::factory()->create(['role' => Role::Staff, 'permissions' => ['dashboard.view']]);
    $first = membershipOf($user);
    $second = Membership::factory()->create(['user_id' => $user->id, 'role' => Role::Staff, 'permissions' => ['dashboard.view']]);

    $this->post('/forgot-password', ['facility_code' => $first->facility->code, 'email' => $user->email]);

    Notification::assertSentTo($user, MembershipPasswordReset::class, function (MembershipPasswordReset $notification) use ($user, $first) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'facility' => $first->facility->code,
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        return true;
    });

    expect(Hash::check('a-brand-new-password', $first->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $second->fresh()->password))->toBeTrue();
});

test('a platform operator resets their account password without a facility code', function () {
    Notification::fake();
    $operator = User::factory()->platformAdmin()->create();

    $this->post('/forgot-password', ['email' => $operator->email]);

    Notification::assertSentTo($operator, ResetPassword::class, function (ResetPassword $notification) use ($operator) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $operator->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        return true;
    });
});

test('a facility administrator resets their password without a facility code', function () {
    Notification::fake();
    [, $admin] = provisionHospital();

    $this->post('/forgot-password', ['email' => $admin->email]);

    Notification::assertSentTo($admin, MembershipPasswordReset::class, fn (MembershipPasswordReset $notification): bool => $notification->membership->is(membershipOf($admin)));
});
