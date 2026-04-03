<?php

return [

    'return_url' => env('FRONTEND_URL', 'http://localhost:5173') . '/booking/payment-result',

    'chapa' => [
        'secret_key'     => env('CHAPA_SECRET_KEY'),
        'public_key'     => env('CHAPA_PUBLIC_KEY'),
        'webhook_secret' => env('CHAPA_WEBHOOK_SECRET', ''),
        'base_url'       => env('CHAPA_BASE_URL', 'https://api.chapa.co/v1'),
    ],

];
