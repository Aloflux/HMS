<?php

namespace App\Services;

use App\Jobs\SendSms;
use App\Models\Patient;
use App\Models\SmsMessage;

class SmsNotifier
{
    public function queue(Patient $patient, string $purpose, string $body): SmsMessage
    {
        $message = SmsMessage::query()->create([
            'facility_id' => $patient->facility_id,
            'patient_id' => $patient->id,
            'phone' => $patient->phone,
            'body' => $body,
            'purpose' => $purpose,
            'status' => 'queued',
        ]);

        SendSms::dispatch($message->id);

        return $message;
    }
}
