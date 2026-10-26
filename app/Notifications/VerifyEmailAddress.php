<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailAddress extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        $minutes = (int) config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Confirm your email to finish setting up '.config('app.name'))
            ->greeting('Welcome to '.config('app.name').'!')
            ->line('Thanks for registering your facility. Confirm this is your email address so we know where to send sign-in and security messages.')
            ->action('Verify email address', $url)
            ->line("This link expires in {$minutes} minutes. After verifying you will set up your facility, wards and beds in a few short steps.")
            ->line('If you did not create an account, you can ignore this email and nothing will happen.');
    }
}
