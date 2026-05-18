<?php

return [
    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'utilisateurs',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'utilisateurs',  // ← changer 'users' en 'utilisateurs'
        ],
    ],

    'providers' => [
        'utilisateurs' => [                // ← renommer 'users' en 'utilisateurs'
            'driver' => 'eloquent',
            'model'  => App\Models\UtilisateurGithub::class,  // ← ton modèle
        ],
    ],

    'passwords' => [
        'utilisateurs' => [
            'provider' => 'utilisateurs',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];