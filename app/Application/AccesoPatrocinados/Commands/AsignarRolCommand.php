<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class AsignarRolCommand
{
    public function __construct(
        public string $usuario_id,
        public string $rol_id,
        public ?string $asignado_por,
    ) {}
}
