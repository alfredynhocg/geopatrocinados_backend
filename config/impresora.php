<?php

return [
    // Puerto COM virtual que Windows asigna al emparejar la impresora por Bluetooth (perfil SPP)
    'puerto' => env('IMPRESORA_PUERTO', 'COM5'),

    // Caracteres por línea en fuente normal (58mm ≈ 32 columnas, 80mm ≈ 48 columnas)
    'ancho_caracteres' => env('IMPRESORA_ANCHO_CARACTERES', 32),
];
