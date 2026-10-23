<?php

return [

    /*
    | Click-to-fill sample accounts are for local demonstration.
    | Keep this false on any shared or production environment.
    */
    'show_demo_login' => (bool) env('HMS_DEMO_LOGIN', false),

    'demo_password' => env('HMS_DEMO_PASSWORD', 'password'),

    'idle_timeout_minutes' => (int) env('HMS_IDLE_TIMEOUT', 15),

    'currency' => 'UGX',

    'demo_accounts' => [
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Administrator · users, prices, and every screen', 'email' => 'admin@stmarys.afyadesk.test'],
        ['facility' => 'AfyaDesk platform', 'role' => 'Platform administrator · facilities and their administrators', 'email' => 'platform@afyadesk.test'],
    ],

];
