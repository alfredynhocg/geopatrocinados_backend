<?php

namespace App\Application\Visitas\Commands;

final readonly class ReasignarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $nuevoTecnicoId,
        public string $assignedBy,
    ) {}
}
