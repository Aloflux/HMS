<?php

return [

    /*
    | Click-to-fill sample accounts. The login screen shows them only when
    | APP_ENV is local or development, and omits them in production.
    */
    'demo_password' => env('HMS_DEMO_PASSWORD', 'password'),

    'idle_timeout_minutes' => (int) env('HMS_IDLE_TIMEOUT', 15),

    /*
    | Messages up to this number are sent during the request.
    | Anything beyond it, or already waiting, is queued so a burst cannot stall the process.
    */
    'mail_queue_after' => (int) env('HMS_MAIL_QUEUE_AFTER', 5),

    'currency' => 'UGX',

    'demo_accounts' => [
        ['facility' => 'Tunzuri platform', 'role' => 'Super user · opens facilities, and does not see clinical records', 'email' => 'platform@tunzuri.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Administrator · the one account this hospital is opened with', 'email' => 'admin@stmarys.tunzuri.test'],
    ],

];
