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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'facebook' => [
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
        'client_token' => env('FACEBOOK_CLIENT_TOKEN'),
    ],

    'whatsapp' => [
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v21.0'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'embedded_config_id' => env('WHATSAPP_EMBEDDED_CONFIG_ID'),
    ],

    'messenger' => [
        'graph_version' => env('MESSENGER_GRAPH_VERSION', 'v21.0'),
        'verify_token' => env('MESSENGER_VERIFY_TOKEN'),
        'app_secret' => env('MESSENGER_APP_SECRET'),
        'page_id' => env('MESSENGER_PAGE_ID'),
        'page_access_token' => env('MESSENGER_PAGE_ACCESS_TOKEN'),
    ],

    /*
    | Baileys (WhatsApp Web protocol) — optional Node service for “Link a device” QR.
    | Not the same as Meta Cloud API; run `baileys-service` separately.
    */
    'baileys' => [
        'enabled' => (bool) env('BAILEYS_SERVICE_ENABLED', false),
        'url' => env('BAILEYS_SERVICE_URL'),
        'secret' => env('BAILEYS_SERVICE_SECRET'),
    ],

];
