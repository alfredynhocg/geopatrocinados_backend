<?php

namespace App\Application\Patrocinados\Commands;

final readonly class UpdateEstadoPatrocinadoCommand
{
    public function __construct(
        public string $id,
        public string $estado,
    ) {}
}
