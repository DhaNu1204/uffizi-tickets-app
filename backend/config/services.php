<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
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

    'bokun' => [
        'access_key' => env('BOKUN_ACCESS_KEY'),
        'secret_key' => env('BOKUN_SECRET_KEY'),
        'base_url' => env('BOKUN_BASE_URL', 'https://api.bokun.io'),
        'uffizi_product_ids' => explode(',', env('UFFIZI_PRODUCT_IDS', '')),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'sms_from' => env('TWILIO_SMS_FROM'),
        'status_callback_url' => env('TWILIO_STATUS_CALLBACK_URL'),
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'eu-west-1'),
        'bucket' => env('AWS_BUCKET'),
    ],

    'vox' => [
        'api_key' => env('VOX_API_KEY'),
        'api_secret' => env('VOX_API_SECRET'),
        'base_url' => env('VOX_BASE_URL', 'https://popguide.herokuapp.com'),
        'environment' => env('VOX_ENVIRONMENT', 'production'),
    ],

];
