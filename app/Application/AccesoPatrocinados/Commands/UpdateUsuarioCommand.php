<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class UpdateUsuarioCommand
{
    public function __construct(
        public string $id,
        public string $nombres,
        public string $apellidos,
        public ?string $telefono,
        public string $estado,
    ) {}
}
