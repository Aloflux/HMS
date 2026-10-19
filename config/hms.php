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
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Receptionist', 'email' => 'receptionist@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Triage nurse', 'email' => 'triage@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Doctor / clinical officer', 'email' => 'doctor@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Ward nurse', 'email' => 'nurse@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Lab technician', 'email' => 'lab@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Radiographer', 'email' => 'radiology@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Pharmacist', 'email' => 'pharmacist@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Cashier', 'email' => 'cashier@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Insurance / claims officer', 'email' => 'claims@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Store keeper', 'email' => 'store@stmarys.afyadesk.test'],
        ['facility' => "St. Mary's Community Hospital", 'role' => 'Administrator', 'email' => 'admin@stmarys.afyadesk.test'],
        ['facility' => 'Hope Clinic', 'role' => 'Administrator', 'email' => 'admin@hope.afyadesk.test'],
        ['facility' => 'AfyaDesk platform', 'role' => 'Platform administrator', 'email' => 'platform@afyadesk.test'],
    ],

];
