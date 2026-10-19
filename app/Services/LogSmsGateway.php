<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGateway
{
    public function send(SmsMessage $message): void
    {
        Log::info('SMS queued for the configured gateway.', [
            'phone' => $message->phone,
            'purpose' => $message->purpose,
            'body' => $message->body,
        ]);

        $message->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
