<?php

use App\Jobs\SendNotification;
use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

test('a single email is sent immediately', function () {
    Queue::fake();
    config(['hms.mail_queue_after' => 1]);

    User::factory()->create()->notify(new VerifyEmailAddress);

    Queue::assertNothingPushed();
});

test('further emails are queued once a burst would stall the request', function () {
    Queue::fake();
    config(['hms.mail_queue_after' => 1]);

    $user = User::factory()->create();

    $user->notify(new VerifyEmailAddress);
    $user->notify(new VerifyEmailAddress);

    Queue::assertPushed(SendNotification::class, 1);
});

test('email is queued when that many messages are already waiting', function () {
    Queue::fake();
    config([
        'hms.mail_queue_after' => 1,
        'queue.default' => 'database',
    ]);

    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => json_encode(['displayName' => SendNotification::class]),
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);

    User::factory()->create()->notify(new VerifyEmailAddress);

    Queue::assertPushed(SendNotification::class, 1);
});
