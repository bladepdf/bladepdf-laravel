<?php

declare(strict_types=1);

return [
    'base_url' => env('BLADEPDF_BASE_URL', 'https://api.bladepdf.com'),

    'token' => env('BLADEPDF_TOKEN'),

    'timeout' => (int) env('BLADEPDF_TIMEOUT', 60),

    'connect_timeout' => (int) env('BLADEPDF_CONNECT_TIMEOUT', 10),

    'retry_times' => (int) env('BLADEPDF_RETRY_TIMES', 1),

    'retry_sleep' => (int) env('BLADEPDF_RETRY_SLEEP', 200),

    'verify_ssl' => filter_var(env('BLADEPDF_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),

    'user_agent' => env('BLADEPDF_USER_AGENT', 'bladepdf-laravel/1.0'),

    'local_hosts' => array_values(array_filter(array_unique([
        parse_url((string) env('APP_URL', ''), PHP_URL_HOST) ?: null,
        'localhost',
        '127.0.0.1',
        '::1',
    ]))),
];
