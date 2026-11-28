<?php

return [
    // msg91 in production; "log" writes the code to storage/logs/laravel.log for testing
    'driver' => env('OTP_DRIVER', 'log'),
    'test_code' => env('OTP_TEST_CODE'),
    'expiry_minutes' => 5,
    'resend_seconds' => 60,
    'max_per_hour' => 5,
    'max_attempts' => 5,

    'msg91' => [
        'auth_key' => env('MSG91_AUTH_KEY'),
        'template_id' => env('MSG91_OTP_TEMPLATE_ID'),
    ],
];
