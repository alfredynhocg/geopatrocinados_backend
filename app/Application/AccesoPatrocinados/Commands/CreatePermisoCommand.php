<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class CreatePermisoCommand
{
    public function __construct(
        public string $nombre,
        public string $modulo,
        public string $accion,
        public ?string $descripcion,
    ) {}
}
