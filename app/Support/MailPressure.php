<?php

namespace App\Support;

use App\Jobs\SendNotification;
use Illuminate\Support\Facades\DB;

class MailPressure
{
    private static int $sent = 0;

    /**
     * A short burst is delivered in the request. Past this many messages, further mail waits on the queue.
     */
    public static function shouldQueue(): bool
    {
        $limit = max(1, (int) config('hms.mail_queue_after', 5));

        return self::$sent >= $limit || self::waiting() >= $limit;
    }

    public static function record(): void
    {
        self::$sent++;
    }

    public static function forget(): void
    {
        self::$sent = 0;
    }

    private static function waiting(): int
    {
        $connection = (string) config('queue.default');
        $driver = config("queue.connections.{$connection}.driver");

        if ($driver !== 'database') {
            return 0;
        }

        $table = config("queue.connections.{$connection}.table", 'jobs');

        return DB::table($table)
            ->where('payload', 'like', '%'.class_basename(SendNotification::class).'%')
            ->count();
    }
}
