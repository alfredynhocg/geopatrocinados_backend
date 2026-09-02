<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class RevocarRolCommand
{
    public function __construct(
        public string $usuario_id,
        public string $rol_id,
    ) {}
}
