<?php

$googleCaFile = env('SSO_GOOGLE_CAFILE');
$googleVerify = env('SSO_GOOGLE_VERIFY_SSL', true);

if ($googleCaFile) {
    $googleCaFile = trim($googleCaFile);
    $isAbsolutePath = str_starts_with($googleCaFile, '/')
        || preg_match('/^[A-Za-z]:[\/\\\\]/', $googleCaFile)
        || str_starts_with($googleCaFile, '\\\\');

    $googleVerify = $isAbsolutePath
        ? $googleCaFile
        : storage_path('app/'.ltrim($googleCaFile, '/\\'));
}

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

    'google' => [
        'enabled' => env('SSO_GOOGLE_ENABLED', false),
        'allowed_domains' => array_filter(array_map('trim', explode(',', env('SSO_GOOGLE_ALLOWED_DOMAINS', '')))),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'guzzle' => [
            'verify' => $googleVerify,
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
