<?php

namespace App\Jobs;

use App\Contracts\SmsGateway;
use App\Models\SmsMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSms implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $smsMessageId) {}

    public function handle(SmsGateway $gateway): void
    {
        $message = SmsMessage::query()->find($this->smsMessageId);

        if (! $message || $message->status === 'sent') {
            return;
        }

        $gateway->send($message);
    }
}
