<?php

return [
    'host' => read_env('EMAIL_HOST'),
    'user' => read_env('EMAIL_USER'),
    'password' => read_env('EMAIL_PASS'),
    'port' => read_env('EMAIL_PORT'),
    'secure' => read_env('EMAIL_SECURE')
];
