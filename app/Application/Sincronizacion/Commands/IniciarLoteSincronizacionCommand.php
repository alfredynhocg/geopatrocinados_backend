<?php

namespace App\Application\Sincronizacion\Commands;

final readonly class IniciarLoteSincronizacionCommand
{
    public function __construct(
        public string $dispositivo_id,
        public string $user_id,
    ) {}
}
