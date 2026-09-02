<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class AsignarPermisoARolCommand
{
    public function __construct(
        public string $rol_id,
        public string $permiso_id,
    ) {}
}
