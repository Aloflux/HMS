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
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Front office · reception and cashier', 'email' => 'front@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Clinical team · triage, doctor, and wards', 'email' => 'doctor@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Diagnostics · laboratory and imaging', 'email' => 'diagnostics@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Pharmacy and stores', 'email' => 'pharmacy@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Administrator', 'email' => 'admin@stmarys.afyadesk.test'],
        ['facility' => 'Hope Clinic', 'role' => 'Administrator', 'email' => 'admin@hope.afyadesk.test'],
        ['facility' => 'AfyaDesk platform', 'role' => 'Platform administrator', 'email' => 'platform@afyadesk.test'],
    ],

];
