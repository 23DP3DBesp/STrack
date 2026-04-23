<?php

return [
    'mailtrap' => [
        'token' => env('MAILTRAP_API_TOKEN'),
        'sender_email' => env('MAILTRAP_SENDER_EMAIL'),
        'sender_name' => env('MAILTRAP_SENDER_NAME', env('APP_NAME', 'STRACK')),
        'category' => env('MAILTRAP_CATEGORY', 'account-verification'),
    ],
];
