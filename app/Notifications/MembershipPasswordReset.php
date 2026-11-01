<?php

namespace App\Notifications;

use App\Models\Membership;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipPasswordReset extends Notification
{
    use Queueable;

    /**
     * @param  string  $token  The plain reset token; only its hash is stored.
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

        return (new MailMessage)
            ->from(config('mail.from.address'), "{$facility->name} via ".config('app.name'))
            ->subject("Reset your {$facility->name} password")
            ->greeting("Hello {$notifiable->name},")
            ->line("Someone asked to reset your password for {$facility->name} (facility code {$facility->code}).")
            ->action('Choose a new password', route('password.reset', ['token' => $this->token, 'facility' => $facility->code]))
            ->line('This changes the password for '.$facility->name.' only. Your other facilities keep theirs.')
            ->line('The link works for '.Membership::PASSWORD_RESET_MINUTES.' minutes. If you did not ask for it, you can ignore this email.');
    }
}
