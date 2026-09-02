<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class CreateRolCommand
{
    public function __construct(
        public string $nombre,
        public ?string $descripcion,
        public bool $estado,
    ) {}
}
