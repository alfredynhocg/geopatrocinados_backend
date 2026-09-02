<?php

namespace App\Application\Dispositivos\Commands;

final readonly class RegistrarDispositivoCommand
{
    public function __construct(
        public string $user_id,
        public string $identificador_dispositivo,
        public ?string $nombre_dispositivo,
        public string $plataforma,
        public ?string $version_sistema,
        public ?string $version_aplicacion,
    ) {}
}
