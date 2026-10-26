<?php

use App\Enums\Role;
use App\Models\Bed;
use App\Models\Facility;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Ward;
use App\Notifications\FacilityReady;
use App\Notifications\VerifyEmailAddress;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * @return array<string, string>
 */
function facilitySignUp(array $overrides = []): array
{
    return [
        'facility_name' => 'Hope Medical Centre',
        'facility_kind' => 'clinic',
        'district' => 'Gulu',
        'name' => 'Grace Akello',
        'email' => 'grace@hope.test',
        'phone' => '0772000111',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
        ...$overrides,
    ];
}

function registeredOwner(): User
{
    return User::query()->where('email', 'grace@hope.test')->firstOrFail();
}

test('the facility sign-up page can be rendered', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Register your facility');
});

test('a facility can sign up and the owner is emailed a verification link', function () {
    Notification::fake();

    $this->post(route('register'), facilitySignUp())
        ->assertRedirect(route('verification.notice'));

    $owner = registeredOwner();
    $facility = $owner->facility;

    $this->assertAuthenticatedAs($owner);
    expect($owner->role)->toBe(Role::Administrator);
    expect($owner->hasVerifiedEmail())->toBeFalse();
    expect($facility->name)->toBe('Hope Medical Centre');
    expect($facility->code)->toStartWith('HMC-');
    expect($facility->isOnboarded())->toBeFalse();
    expect(Ward::query()->where('facility_id', $facility->id)->count())->toBe(2);

    Notification::assertSentTo($owner, VerifyEmailAddress::class, function (VerifyEmailAddress $notification) use ($owner): bool {
        $mail = $notification->toMail($owner);

        return str_contains($mail->actionUrl, '/verify-email/'.$owner->id.'/')
            && str_contains((string) $mail->render(), 'Verify email address');
    });
});

test('sign-up rejects an email that is already registered', function () {
    Notification::fake();
    User::factory()->create(['email' => 'grace@hope.test']);

    $this->post(route('register'), facilitySignUp())
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(Facility::query()->where('name', 'Hope Medical Centre')->exists())->toBeFalse();
});

test('an unverified owner is held at the verification notice', function () {
    Notification::fake();
    $this->post(route('register'), facilitySignUp());

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->get(route('onboarding.show'))->assertRedirect(route('verification.notice'));
    $this->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('grace@hope.test');
});

test('verifying the email sends the owner into the setup wizard', function () {
    Notification::fake();
    $this->post(route('register'), facilitySignUp());
    $owner = registeredOwner();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $owner->id, 'hash' => sha1($owner->email)],
    );

    $this->get($verificationUrl)->assertRedirect(route('dashboard').'?verified=1');
    expect($owner->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('dashboard'))->assertRedirect(route('onboarding.show'));
    $this->get(route('onboarding.show'))
        ->assertOk()
        ->assertSee('Facility details');
});

test('the owner can complete the setup wizard and go live', function () {
    Notification::fake();
    $this->post(route('register'), facilitySignUp());
    $owner = registeredOwner();
    $owner->markEmailAsVerified();
    $this->actingAs($owner->fresh());
    $facility = $owner->facility;

    $this->post(route('onboarding.facility'), [
        'name' => 'Hope Medical Centre Gulu',
        'kind' => 'clinic',
        'district' => 'Gulu',
        'address' => 'Plot 4, Market Street',
        'phone' => '0772000111',
        'email' => 'info@hope.test',
    ])->assertRedirect(route('onboarding.show', 'wards'));

    expect($facility->fresh()->address)->toBe('Plot 4, Market Street');

    [$female, $male] = Ward::query()->where('facility_id', $facility->id)->orderBy('id')->get()->all();

    $this->post(route('onboarding.wards'), [
        'wards' => [
            $female->id => ['name' => 'General ward', 'beds' => 6],
            $male->id => ['name' => 'Recovery ward', 'beds' => 2],
        ],
        'new_wards' => [
            ['name' => "Children's ward", 'beds' => 3],
            ['name' => '', 'beds' => 4],
        ],
    ])->assertRedirect(route('onboarding.show', 'finish'));

    expect($female->fresh()->name)->toBe('General ward');
    expect($female->beds()->count())->toBe(6);
    expect($male->beds()->orderBy('id')->pluck('code')->all())->toBe(['Bed 1', 'Bed 2']);

    $children = Ward::query()->where('facility_id', $facility->id)->where('name', "Children's ward")->firstOrFail();
    expect($children->code)->toBe('CW');
    expect($children->beds()->count())->toBe(3);
    expect(StockLocation::query()->where('facility_id', $facility->id)->where('name', "Children's ward store")->exists())->toBeTrue();
    expect(Ward::query()->where('facility_id', $facility->id)->count())->toBe(3);

    $this->post(route('onboarding.finish'))
        ->assertRedirect(route('dashboard'));

    $facility->refresh();
    expect($facility->isOnboarded())->toBeTrue();
    expect($facility->bed_capacity)->toBe(Bed::query()->where('facility_id', $facility->id)->count());
    Notification::assertSentTo($owner, FacilityReady::class);

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('onboarding.show'))->assertRedirect(route('dashboard'));
});

test('only the facility administrator can use the setup wizard', function () {
    $facility = Facility::factory()->pendingOnboarding()->create();
    $staff = User::factory()->create(['facility_id' => $facility->id, 'role' => Role::FrontOffice]);

    $this->actingAs($staff)->get(route('onboarding.show'))->assertForbidden();
    $this->actingAs($staff)->post(route('onboarding.finish'))->assertForbidden();

    expect($facility->fresh()->isOnboarded())->toBeFalse();
});

test('the go-live email lists the next steps', function () {
    $facility = Facility::factory()->create(['name' => 'Hope Medical Centre']);
    $owner = User::factory()->create(['facility_id' => $facility->id, 'role' => Role::Administrator]);

    $html = (string) (new FacilityReady($facility))->toMail($owner)->render();

    expect($html)
        ->toContain('Hope Medical Centre is set up')
        ->toContain('Open your dashboard')
        ->toContain($facility->code);
});
