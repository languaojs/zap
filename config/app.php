<?php

/**
 * production_mode => 'auto' or 'fast'
 * It sets BladeOne:: MODE to either MODE_AUTO or MODE_FAST
 */

return [
    'long_name' => 'Zap PHP Framework',
    'short_name' => 'ZapPHP',
    'session' => read_env('SESSION_NAME'),
    'environment' => read_env('ENVIRONMENT'),
    'key' => read_env('ZAP_KEY'),
    'dev_server' => read_env('DEV_SERVER'),
    'production_mode' => 'auto',
    'setup' => true,
    'allowed_roots' => [
        realpath(BASE_PATH . '/storage'),
        realpath(BASE_PATH . '/public'),
        realpath(BASE_PATH . '/CLI'),
        realpath(BASE_PATH . '/migrations'),

    ],
    'downloadable_roots' => [
        BASE_PATH . '/storage/downloads',
        BASE_PATH . '/storage/exports',
        BASE_PATH . '/public/media',
    ]
];
