<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    | Configured to accept requests from the Vue 3 frontend (Vite dev server)
    | and production domain.
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:4173',
        'http://localhost:3000',
        'http://192.168.137.171:5173',
        'http://192.168.137.171:4173',
        env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    | Set to true when using Sanctum cookie-based auth (SPA).
    | Set to false if using token-based auth only.
    */
    'supports_credentials' => false,

];
