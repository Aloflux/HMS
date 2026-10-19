<?php

namespace App\Contracts;

use App\Models\SmsMessage;

interface SmsGateway
{
    public function send(SmsMessage $message): void;
}
