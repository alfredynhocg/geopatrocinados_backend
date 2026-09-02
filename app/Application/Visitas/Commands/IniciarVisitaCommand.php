<?php

namespace App\Application\Visitas\Commands;

final readonly class IniciarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $dispositivoId,
        public string $ejecutadoPor,
    ) {}
}
