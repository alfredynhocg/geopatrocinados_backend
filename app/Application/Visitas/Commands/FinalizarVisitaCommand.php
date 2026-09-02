<?php

namespace App\Application\Visitas\Commands;

final readonly class FinalizarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $estadoFinal, // FINALIZADA | NO_ENCONTRADO | CANCELADA
        public string $ejecutadoPor,
    ) {}
}
