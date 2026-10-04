<?php

return [
    'host' => read_env('DB_HOST'),
    'database' => read_env('DB_NAME'),
    'user' => read_env('DB_USER'),
    'password' => read_env('DB_PASS'),
    'driver' => read_env('DB_DRIVER'),
    'port' => read_env('DB_PORT'),
    'sqlite_path' => read_env('SQLITE_PATH')
];
