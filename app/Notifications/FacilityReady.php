<?php

namespace App\Notifications;

use App\Models\Facility;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FacilityReady extends Notification
{
    public function __construct(public Facility $facility) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->facility->name.' is live on '.config('app.name'))
            ->greeting('You are all set, '.$notifiable->name.'.')
            ->line("{$this->facility->name} is set up and ready to receive patients.")
            ->line('A few things worth doing next:')
            ->line('• Add your staff on the Users page and tick the areas each person works in.')
            ->line('• Review the price list so registration, consultation and bed-day charges match your facility.')
            ->line('• Adjust wards and beds at any time from Administration → Wards & beds.')
            ->action('Open your dashboard', route('dashboard'))
            ->line('Your facility code is '.$this->facility->code.'. Keep it handy when you contact support.');
    }
}
