<?php

namespace App\Notifications;

use App\Models\Membership;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipInvitation extends Notification
{
    use Queueable;

    /**
     * @param  string  $token  The plain invitation token; only its hash is stored.
     */
    public function __construct(public Membership $membership, public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $facility = $this->membership->facility;
        $inviter = $this->membership->inviter?->name ?? 'An administrator';
        $place = $facility->district ? "{$facility->name} ({$facility->district})" : $facility->name;

        return (new MailMessage)
            ->from(config('mail.from.address'), "{$facility->name} via ".config('app.name'))
            ->subject("Join {$facility->name} on ".config('app.name'))
            ->greeting('Hello '.($this->membership->invited_name ?? $notifiable->name).',')
            ->line("{$inviter} has invited you to work at {$place} on ".config('app.name').'.')
            ->line('Access: '.$this->membership->accessAreas().'.')
            ->action("Join {$facility->name}", route('invitations.show', $this->token))
            ->line('The link works for '.Membership::INVITATION_DAYS.' days. If you were not expecting it, you can ignore this email.');
    }
}
