<?php

namespace App\Support;

class Money
{
    public static function format(int $amount): string
    {
        return 'UGX '.number_format($amount);
    }
}
