<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class UpdatePermisoCommand
{
    public function __construct(
        public string $id,
        public string $nombre,
        public string $modulo,
        public string $accion,
        public ?string $descripcion,
    ) {}
}
