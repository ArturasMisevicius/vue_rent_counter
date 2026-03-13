<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for authentication-related endpoints to prevent brute force
    | attacks and credential stuffing.
    |
    | Format: 'max_attempts' => number of attempts allowed
    |         'decay_minutes' => time window in minutes
    |
    */

    'login' => [
        'max_attempts' => env('THROTTLE_LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('THROTTLE_LOGIN_DECAY_MINUTES', 1),
    ],

    'register' => [
        'max_attempts' => env('THROTTLE_REGISTER_MAX_ATTEMPTS', 3),
        'decay_minutes' => env('THROTTLE_REGISTER_DECAY_MINUTES', 60),
    ],

    'password_reset' => [
        'max_attempts' => env('THROTTLE_PASSWORD_RESET_MAX_ATTEMPTS', 3),
        'decay_minutes' => env('THROTTLE_PASSWORD_RESET_DECAY_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for API endpoints.
    |
    */

    'api' => [
        'max_attempts' => env('THROTTLE_API_MAX_ATTEMPTS', 60),
        'decay_minutes' => env('THROTTLE_API_DECAY_MINUTES', 1),
    ],

];
