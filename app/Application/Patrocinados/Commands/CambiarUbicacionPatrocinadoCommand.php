<?php

namespace App\Application\Patrocinados\Commands;

final readonly class CambiarUbicacionPatrocinadoCommand
{
    public function __construct(
        public string $patrocinado_id,
        public string $comunidad_id,
        public ?string $ubicacion_id,
        public ?string $usuario_id,
    ) {}
}
