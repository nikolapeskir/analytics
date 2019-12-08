<?php

return [
    'web' => [
        'client_id' => env('GOOGLE_ANALYTICS_CLIENT_ID'),
        'project_id' => env('GOOGLE_ANALYTICS_PROJECT_ID'),
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_secret' => env('GOOGLE_ANALYTICS_CLIENT_SECRET'),
        'redirect_uris' => [env('APP_URL') . env('GOOGLE_ANALYTICS_CALLBACK_URI')],
        'javascript_origins' => [env('APP_URL')]
    ],
    'authenticate' => env('GOOGLE_ANALYTICS_AUTH_URI'),
    'cache_lifetime_in_minutes' => 60 * 24,
    'cache' => [
        'store' => 'file',
    ],
];