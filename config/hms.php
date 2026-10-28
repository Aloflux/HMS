<?php

return [

    /*
    | Shared password for the login screen's click-to-fill list.
    | That list is read from the users table, includes only accounts that
    | still use this password, and is shown only when APP_ENV is local
    | or development.
    */
    'demo_password' => env('HMS_DEMO_PASSWORD', 'password'),

    'idle_timeout_minutes' => (int) env('HMS_IDLE_TIMEOUT', 15),

    /*
    | Messages up to this number are sent during the request.
    | Anything beyond it, or already waiting, is queued so a burst cannot stall the process.
    */
    'mail_queue_after' => (int) env('HMS_MAIL_QUEUE_AFTER', 5),

    'currency' => 'UGX',

];
