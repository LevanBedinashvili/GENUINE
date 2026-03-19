<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bank of Georgia Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for BOG Payments API v1 integration.
    | API Documentation: https://api.bog.ge/docs/payments/
    |
    */

    // OAuth2 Credentials (provided by BOG)
    'client_id' => env('BOG_CLIENT_ID', ''),
    'client_secret' => env('BOG_CLIENT_SECRET', ''),
    
    // Public key for callback signature verification (provided by BOG)
    'public_key' => env('BOG_PUBLIC_KEY', ''),
    
    // API Endpoints
    'base_url' => env('BOG_MODE') === 'sandbox' 
        ? 'https://api.sandbox.bog.ge/payments/v1' 
        : 'https://api.bog.ge/payments/v1',
    'auth_url' => env('BOG_MODE') === 'sandbox' 
        ? 'https://oauth2.sandbox.bog.ge/auth/realms/bog/protocol/openid-connect/token' 
        : 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token',
    'payment_url' => env('BOG_MODE') === 'sandbox' 
        ? 'https://payment.sandbox.bog.ge' 
        : 'https://payment.bog.ge',
    
    // Default language for payment page (ka or en)
    'language' => env('BOG_LANGUAGE', 'ka'),
    
    // Default theme for payment page (light or dark)
    'theme' => env('BOG_THEME', 'light'),
    
    // Capture mode: 'automatic' or 'manual'
    'capture' => env('BOG_CAPTURE', 'automatic'),
];
