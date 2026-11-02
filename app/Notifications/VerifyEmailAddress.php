<?php

namespace App\Notifications;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class VerifyEmailAddress extends VerifyEmail
{
    /**
     * The facilities this person belongs to, so the email can give the codes they sign in with.
     *
     * @var Collection<int, Facility>
     */
    private Collection $facilities;

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $this->facilities = $notifiable instanceof User
            ? $notifiable->memberships()->whereNotNull('accepted_at')->with('facility')->get()->pluck('facility')
            : new Collection;

        return parent::toMail($notifiable);
    }

    /**
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        $minutes = (int) config('auth.verification.expire', 60);

        $message = (new MailMessage)
            ->subject('Confirm your email to finish setting up '.config('app.name'))
            ->greeting('Welcome to '.config('app.name').'!')
            ->line('Thanks for registering your facility. Confirm this is your email address so we know where to send sign-in and security messages.')
            ->action('Verify email address', $url);

        if ($this->facilities->count() === 1) {
            $facility = $this->facilities->first();
            $message->line("Your facility code for {$facility->name} is **{$facility->code}**. You sign in with this code, your email and your password, so keep it somewhere safe.");
        } elseif ($this->facilities->isNotEmpty()) {
            $message->line('You sign in with a facility code, your email and that facility\'s password. Your codes: '
                .$this->facilities->map(fn (Facility $facility): string => "{$facility->name} **{$facility->code}**")->implode(', ').'.');
        }

        return $message
            ->line("This link expires in {$minutes} minutes. After verifying you will set up your facility, wards and beds in a few short steps.")
            ->line('If you did not create an account, you can ignore this email and nothing will happen.');
    }
}
