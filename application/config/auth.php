<?php

return [
    'defaults' => [
        'guard'     => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver'   => 'keycloak',
            'provider' => 'jwt-users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
        ],
        'jwt-users' => [
            'driver' => 'jwt-payload-users',
            'model'  => KeycloakAuthGuard\Models\JwtPayloadUser::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider'  => 'users',
            'table'     => 'password_reset_tokens',
            'expire'    => 60,
            'throttle'  => 60,
        ],
    ],

    'password_timeout' => 10800,
];
