<?php

return [
    'flasher' => [
        'local' => 'flasher.js',
        'cdn' => 'none',
        'default' => 'footer',
        'type' => 'text/javascript',
        'attributes' => '',
    ],
    'main' => [
        'local' => 'main.js',
        'cdn' => 'none',
        'default' => 'none',
        'type' => 'module',
        'attributes' => '',
    ],
    'litewire' => [
        'local' => 'litewire.min.js',
        'cdn' => 'none',
        'default' => 'none',
        'type' => 'module',
        'attributes' => 'defer'
    ],
    'litewire-auto-csrf-plugin' => [
        'local' => 'litewire-auto-csrf-plugin.js',
        'cdn' => 'none',
        'default' => 'none',
        'type' => 'text/javascript',
        'attributes' => ''
    ]
];