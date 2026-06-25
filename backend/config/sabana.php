<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sabana Center Configuration
    |--------------------------------------------------------------------------
    |
    | Prefix untuk rute internal pegawai. Menggunakan config() memungkinkan 
    | aplikasi tetap berjalan normal saat menjalankan config:cache.
    |
    */
    'portal_prefix' => env('PORTAL_INTERNAL_PREFIX', 'sabana-center-default'),
     'rate_limit' => [
        'max_attempts' => (int) env('ADMIN_RATE_LIMIT_MAX', 3),
        'decay_minutes' => (int) env('ADMIN_RATE_LIMIT_DECAY', 5),
    ],

    'smart' => [
        'thresholds' => [
            'highly_recommended' => 0.70,
            'recommended'        => 0.50,
            'considered'         => 0.30,
        ],
    ],
];