<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fonnte / WhatsApp Integration
    |--------------------------------------------------------------------------
    */

    'fonnte' => [
        // Kompatibilitas untuk kode lama
        'token' => env('FONNTE_TOKEN'),
        'device_number' => env('FONNTE_DEVICE_NUMBER'),

        // Token resmi per peran
        'admin_token' => env('FONNTE_ADMIN_TOKEN', env('FONNTE_TOKEN')),
        'guru_token' => env('FONNTE_GURU_TOKEN', env('FONNTE_TOKEN')),

        // Device resmi per peran
        'admin_device_number' => env('FONNTE_ADMIN_DEVICE_NUMBER', '6285601820651'),
        'guru_device_number' => env('FONNTE_GURU_DEVICE_NUMBER', '6283190007144'),

        // Mapping user internal default
        'default_admin_user_id' => env('FONNTE_DEFAULT_ADMIN_ID', 1),
        'default_guru_user_id' => env('FONNTE_DEFAULT_GURU_ID', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | SIA Realtime API Integration
    |--------------------------------------------------------------------------
    |
    | SINTA terintegrasi ke SIA sepenuhnya melalui API realtime.
    | Tidak menggunakan koneksi database lokal SIA.
    |
    */

    'sia' => [
        'base_url' => rtrim((string) env('SIA_BASE_URL', 'https://unkeeled-naturally-lacie.ngrok-free.dev'), '/'),
        'token' => env('SIA_TOKEN', 'rahasia-sinta-123'),
        'public_url' => env('SIA_PUBLIC_URL', env('SIA_BASE_URL')),
        'kepsek_nuptk' => env('KEPSEK_NUPTK'),
    ],
];