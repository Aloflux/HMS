<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it creates a verified platform administrator from interactive prompts', function () {
    $this->artisan('hms:create-platform-admin')
        ->expectsQuestion('Email address', 'Ops@Example.test')
        ->expectsQuestion('Full name', 'Platform Operator')
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsQuestion('Confirm password', 'a-strong-password')
        ->assertSuccessful();

    $operator = User::query()->where('email', 'ops@example.test')->firstOrFail();

    expect($operator->role)->toBe(Role::PlatformAdmin)
        ->and($operator->facility_id)->toBeNull()
        ->and($operator->is_active)->toBeTrue()
        ->and($operator->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('a-strong-password', $operator->password))->toBeTrue();
});

test('it asks again when the password confirmation does not match', function () {
    $this->artisan('hms:create-platform-admin', ['--email' => 'ops@example.test', '--name' => 'Platform Operator'])
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsQuestion('Confirm password', 'a-different-password')
        ->expectsOutputToContain('The passwords do not match')
        ->expectsQuestion('Password', 'a-strong-password')
        ->expectsQuestion('Confirm password', 'a-strong-password')
        ->assertSuccessful();

    expect(Hash::check('a-strong-password', User::query()->where('email', 'ops@example.test')->value('password')))->toBeTrue();
});

test('it refuses to run without an interactive terminal', function () {
    $this->artisan('hms:create-platform-admin', ['--email' => 'ops@example.test', '--name' => 'Platform Operator', '--no-interaction' => true])
        ->expectsOutputToContain('must be run interactively')
        ->assertFailed();

    expect(User::query()->where('email', 'ops@example.test')->exists())->toBeFalse();
});

test('it will not turn a facility account into a platform administrator', function () {
    [, $admin] = provisionHospital();

    $this->artisan('hms:create-platform-admin', ['--email' => $admin->email])
        ->expectsOutputToContain('already belongs to a facility account')
        ->assertFailed();

    expect($admin->fresh()->role)->toBe(Role::Administrator);
});

test('it resets an existing platform administrator password only after confirmation', function () {
    $operator = User::factory()->create([
        'role' => Role::PlatformAdmin,
        'facility_id' => null,
        'is_active' => false,
        'password' => 'old-password-123',
    ]);

    $this->artisan('hms:create-platform-admin', ['--email' => $operator->email])
        ->expectsConfirmation("{$operator->name} is already a platform administrator. Reset their password?", 'no')
        ->assertSuccessful();

    expect(Hash::check('old-password-123', $operator->fresh()->password))->toBeTrue();

    $this->artisan('hms:create-platform-admin', ['--email' => $operator->email])
        ->expectsConfirmation("{$operator->name} is already a platform administrator. Reset their password?", 'yes')
        ->expectsQuestion('Password', 'a-new-strong-password')
        ->expectsQuestion('Confirm password', 'a-new-strong-password')
        ->assertSuccessful();

    $operator->refresh();
    expect(Hash::check('a-new-strong-password', $operator->password))->toBeTrue()
        ->and($operator->is_active)->toBeTrue();
});

test('it rejects a password that does not meet the password rules', function () {
    $this->artisan('hms:create-platform-admin', ['--email' => 'ops@example.test', '--name' => 'Platform Operator'])
        ->expectsQuestion('Password', 'short')
        ->expectsOutputToContain('at least 8 characters')
        ->assertFailed();

    expect(User::query()->where('email', 'ops@example.test')->exists())->toBeFalse();
});
