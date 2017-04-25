<?php

return [
    'path' => env(base_path('GKR_THEMES_PATH'),base_path('themes')),
    'cache' => [
        'enabled' => false,
        'key' => 'gkr.themes',
        'lifetime' => 86400,
    ],
];
