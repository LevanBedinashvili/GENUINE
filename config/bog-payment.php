<?php

return [
    'bog' => [
        'client_id' => env('BOG_CLIENT_ID', ''),
        'client_secret' => env('BOG_CLIENT_SECRET', ''),
        'public_key' => env('BOG_PUBLIC_KEY', ''),
        'mode' => env('BOG_MODE', 'sandbox'), // 'sandbox' or 'production'
    ],
];
