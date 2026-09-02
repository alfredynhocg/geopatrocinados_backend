<?php

namespace App\Application\Visitas\Commands;

final readonly class ReprogramarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $ejecutadoPor,
    ) {}
}
