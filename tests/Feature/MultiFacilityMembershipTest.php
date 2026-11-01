<?php

use App\Enums\Role;
use App\Http\Middleware\SetFacilityContext;
use App\Models\AuditLog;
use App\Models\Membership;
use App\Models\User;
use App\Notifications\MembershipInvitation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Notification;

/**
 * @return array<string, mixed>
 */
function invitation(string $email, string $name = 'Jane Invited'): array
{
    return ['name' => $name, 'email' => $email, 'account' => 'staff', 'areas' => ['laboratory']];
}

/**
 * Invite someone and return the plain token from the email they receive.
 */
function inviteAndCaptureToken(User $admin, string $email): string
{
    Notification::fake();
    test()->actingAs($admin)->post(route('admin.users.store'), invitation($email));

    $token = null;
    Notification::assertSentTo(User::query()->where('email', $email)->first(), MembershipInvitation::class, function (MembershipInvitation $mail) use (&$token): bool {
        $token = $mail->token;

        return true;
    });

    return $token;
}

test('inviting someone who works at another facility looks exactly like inviting someone new', function () {
    [, $adminA] = provisionHospital();
    [$facilityB, $adminB] = provisionHospital();
    $elsewhere = User::factory()->create(['facility_id' => $facilityB->id, 'role' => Role::Staff, 'name' => 'Real Name Elsewhere', 'email' => 'jane@example.test']);
    Notification::fake();

    $this->actingAs($adminA)->post(route('admin.users.store'), invitation('jane@example.test'))
        ->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors()->assertSessionHas('status', 'Invitation sent to jane@example.test.');
    $this->actingAs($adminA)->post(route('admin.users.store'), invitation('brand.new@example.test'))
        ->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors()->assertSessionHas('status', 'Invitation sent to brand.new@example.test.');

    $this->actingAs($adminA)
        ->get(route('admin.users.index'))
        ->assertSee('Jane Invited')
        ->assertDontSee('Real Name Elsewhere');

    $audited = function (string $email) use ($adminA): array {
        $person = User::query()->where('email', $email)->firstOrFail();
        $membership = membershipOf($person, membershipOf($adminA)->facility);

        return AuditLog::query()->withoutGlobalScopes()
            ->where('facility_id', $membership->facility_id)
            ->where(fn ($query) => $query
                ->where(fn ($user) => $user->where('auditable_type', $person->getMorphClass())->where('auditable_id', $person->id))
                ->orWhere(fn ($row) => $row->where('auditable_type', $membership->getMorphClass())->where('auditable_id', $membership->id)))
            ->orderBy('id')
            ->get()
            ->map(fn (AuditLog $log): string => $log->auditable_type.':'.$log->action)
            ->all();
    };
    expect($audited('jane@example.test'))->not->toBeEmpty()->toBe($audited('brand.new@example.test'));

    expect($elsewhere->fresh()->name)->toBe('Real Name Elsewhere');
    Notification::assertSentTo($elsewhere, MembershipInvitation::class);
});

test('adding someone who is already a member here is refused and says so', function () {
    [$facility, $admin] = provisionHospital();
    User::factory()->create(['facility_id' => $facility->id, 'role' => Role::Staff, 'name' => 'Grace Here', 'email' => 'grace@example.test']);

    $this->actingAs($admin)
        ->from(route('admin.users.create'))
        ->post(route('admin.users.store'), invitation('Grace@Example.test'))
        ->assertSessionHasErrors(['email' => 'Grace Here is already a member of this facility.']);

    expect(Membership::query()->count())->toBe(2);
});

test('a new person accepts by choosing a password and starts work with the access they were given', function () {
    [$facility, $admin] = provisionHospital();
    $token = inviteAndCaptureToken($admin, 'new.tech@example.test');
    $this->post('/logout');

    $this->get(route('invitations.show', $token))->assertOk()->assertSee('Choose a password');
    $this->post(route('invitations.accept', $token), [
        'name' => 'New Tech',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
    ])->assertRedirect(route('dashboard'));

    $person = User::query()->where('email', 'new.tech@example.test')->first();
    $this->assertAuthenticatedAs($person);
    expect($person->name)->toBe('New Tech')
        ->and($person->facility_id)->toBe($facility->id)
        ->and($person->permits('lab.manage'))->toBeTrue()
        ->and($person->permits('cashier.manage'))->toBeFalse();

    $this->get(route('invitations.show', $token))->assertSee('This invitation cannot be used');
});

test('someone with an account elsewhere signs in to accept, then chooses and switches facility', function () {
    [$facilityA, $adminA] = provisionHospital();
    [$facilityB, $adminB] = provisionHospital();
    $person = User::factory()->create(['facility_id' => $facilityB->id, 'role' => Role::Administrator, 'email' => 'both@example.test']);
    $visitAtB = openCashVisit($facilityB, $adminB);
    $token = inviteAndCaptureToken($adminA, 'both@example.test');
    $this->post('/logout');

    $this->get(route('invitations.show', $token))->assertSee('Sign in to accept');
    $this->post(route('login'), ['email' => 'both@example.test', 'password' => 'password'])
        ->assertRedirect(route('invitations.show', $token));
    $this->post(route('invitations.accept', $token))->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('visits.show', $visitAtB))->assertNotFound();

    $this->post('/logout');
    $this->post(route('login'), ['email' => 'both@example.test', 'password' => 'password']);
    $this->get(route('dashboard'))->assertRedirect(route('facilities.choose'));
    $this->get(route('facilities.choose'))->assertOk()->assertSee($facilityA->name)->assertSee($facilityB->name);

    $this->post(route('facilities.switch', membershipOf($person, $facilityB)))->assertRedirect(route('dashboard'));
    $this->get(route('visits.show', $visitAtB))->assertOk();
});

test('nobody can switch into a facility they do not work at', function () {
    [, $adminA] = provisionHospital();
    [, $adminB] = provisionHospital();

    $this->actingAs($adminA)
        ->post(route('facilities.switch', membershipOf($adminB)))
        ->assertNotFound();
});

test('turning someone off at one facility leaves their other facilities working', function () {
    [$facilityA, $adminA] = provisionHospital();
    [$facilityB] = provisionHospital();
    $person = User::factory()->create(['facility_id' => $facilityA->id, 'role' => Role::Staff, 'permissions' => ['dashboard.view']]);
    Membership::factory()->create(['user_id' => $person->id, 'facility_id' => $facilityB->id, 'role' => Role::Staff, 'permissions' => ['dashboard.view']]);

    $this->actingAs($adminA)
        ->patch(route('admin.users.update', membershipOf($person, $facilityA)), ['account' => 'staff', 'areas' => ['reports'], 'is_active' => '0'])
        ->assertRedirect(route('admin.users.index'));

    $this->post('/logout');
    $this->post(route('login'), ['email' => $person->email, 'password' => 'password']);
    $this->get(route('dashboard'))->assertOk();
    expect(session(SetFacilityContext::SESSION_KEY))->toBe(membershipOf($person, $facilityB)->id);
});

test('an administrator only sees when someone last signed in at their own facility', function () {
    [$facilityA, $adminA] = provisionHospital();
    [$facilityB] = provisionHospital();
    $person = User::factory()->create(['facility_id' => $facilityB->id, 'role' => Role::Staff, 'permissions' => ['dashboard.view']]);
    Membership::factory()->create(['user_id' => $person->id, 'facility_id' => $facilityA->id, 'role' => Role::Staff, 'permissions' => ['dashboard.view']]);
    membershipOf($person, $facilityB)->update(['last_login_at' => now()->subHour()]);

    $this->actingAs($adminA)
        ->get(route('admin.users.edit', membershipOf($person, $facilityA)))
        ->assertSee('Last signed in here never');
});

test('withdrawing an invitation removes an account that was never used', function () {
    [, $admin] = provisionHospital();
    inviteAndCaptureToken($admin, 'changed.mind@example.test');
    $membership = Membership::query()->whereNull('accepted_at')->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.users.invitation.cancel', $membership))
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->where('email', 'changed.mind@example.test')->exists())->toBeFalse();
});

test('an expired invitation cannot be accepted', function () {
    [, $admin] = provisionHospital();
    $token = inviteAndCaptureToken($admin, 'late@example.test');
    Membership::query()->whereNull('accepted_at')->update(['invited_at' => now()->subDays(Membership::INVITATION_DAYS + 1)]);
    $this->post('/logout');

    $this->get(route('invitations.show', $token))
        ->assertSee('This invitation cannot be used')
        ->assertSee('Your invitation to '.membershipOf($admin)->facility->name, false);
    $this->post(route('invitations.accept', $token), [
        'name' => 'Late',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
    ])->assertNotFound();
});

test('administrators can no longer change a member\'s name, email or password', function () {
    [$facility, $admin] = provisionHospital();
    $nurse = User::factory()->create(['facility_id' => $facility->id, 'role' => Role::Staff, 'permissions' => [], 'name' => 'Grace Nurse']);
    Notification::fake();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', membershipOf($nurse)), [
            'name' => 'Someone Else',
            'email' => 'taken.over@example.test',
            'password' => 'chosen-by-admin',
            'account' => 'staff',
            'areas' => ['reports'],
            'is_active' => '1',
        ]);

    $nurse->refresh();
    expect($nurse->name)->toBe('Grace Nurse')
        ->and($nurse->email)->not->toBe('taken.over@example.test')
        ->and(Hash::check('password', $nurse->password))->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin.users.password-link', membershipOf($nurse)))
        ->assertSessionHas('status', "Password reset link sent to {$nurse->email}.");
});

test('each invitation says which facility it is from', function () {
    [$facility, $admin] = provisionHospital();
    Notification::fake();

    $this->actingAs($admin)->post(route('admin.users.store'), invitation('multi@example.test'));

    Notification::assertSentTo(User::query()->where('email', 'multi@example.test')->first(), MembershipInvitation::class, function (MembershipInvitation $invitation, array $channels, User $notifiable) use ($facility): bool {
        $mail = $invitation->toMail($notifiable);

        return $mail->from[1] === "{$facility->name} via ".config('app.name')
            && str_contains($mail->subject, $facility->name)
            && $mail->actionText === "Join {$facility->name}"
            && str_contains(implode(' ', $mail->introLines), "{$facility->name} ({$facility->district})");
    });
});

test('the seeded people show each multi-facility case', function () {
    $this->seed(DatabaseSeeder::class);
    $stMarysAdmin = User::query()->where('email', 'admin@stmarys.tunzuri.test')->firstOrFail();

    $this->post(route('login'), ['email' => 'grace.atim@tunzuri.test', 'password' => 'password']);
    $this->get(route('dashboard'))->assertRedirect(route('facilities.choose'));
    $this->post('/logout');

    $this->post(route('login'), ['email' => 'brian.mugisha@tunzuri.test', 'password' => 'password']);
    $this->get(route('dashboard'))->assertOk()->assertSee('Gulu Family Health Clinic');
    $this->post('/logout');

    $this->actingAs($stMarysAdmin)
        ->get(route('admin.users.index'))
        ->assertSee('Agnes L.')
        ->assertDontSee('Agnes Lamunu')
        ->assertSee('Invited');
});
