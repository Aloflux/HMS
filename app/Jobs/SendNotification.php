<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationSender;

class SendNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user, public Notification $notification) {}

    public function handle(): void
    {
        NotificationSender::sendNow($this->user, $this->notification);
    }
}
