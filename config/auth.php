<?php

return [

    

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Guard aislado del módulo Patrocinados — nunca comparte tokens ni
        // provider con 'sanctum' (mentabit, conexión mysql). Ver
        // App\Infrastructure\AccesoPatrocinados\Guards\PatrocinadosTokenGuard.
        'patrocinados' => [
            'driver' => 'patrocinados',
            'provider' => null,
        ],
    ],

    

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Infrastructure\Usuarios\Models\User::class),
        ],

        
        
        
        
    ],

    

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
