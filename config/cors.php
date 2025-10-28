<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'https://frontend-ficct.web.app',
        'https://www.frontend-ficct.web.app',
        'https://parcial2-si-1-frontend-v4pv.vercel.app',
    ],

    'allowed_origins_patterns' => ['/^http:\/\/localhost.*$/', '/.*frontend-ficct.*/', '/.*vercel.app.*$/'],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 86400,

    'supports_credentials' => true,

];
