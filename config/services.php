<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    /*
    |--------------------------------------------------------------------------
    | AI Provider (Gemini Flash by default)
    |--------------------------------------------------------------------------
    |
    | Set GOOGLE_API_KEY in .env to enable Gemini-backed calorie estimation
    | and dashboard insights. Leave it blank for the built-in heuristic
    | fallback that works without any external API calls.
    |
    */

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
        'api_key' => env('GOOGLE_API_KEY'),
        'model_estimate' => env('AI_MODEL_ESTIMATE', 'gemini-2.5-flash'),
        'model_insight' => env('AI_MODEL_INSIGHT', 'gemini-2.5-flash'),
    ],

];
