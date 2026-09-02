<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class UpdateRolCommand
{
    public function __construct(
        public string $id,
        public string $nombre,
        public ?string $descripcion,
        public bool $estado,
    ) {}
}
