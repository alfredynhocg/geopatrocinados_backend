<?php

namespace App\Application\Visitas\Commands;

final readonly class CreateObservacionVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $dispositivoId,
        public ?string $categoriaId,
        public string $tipo,
        public string $observacion,
        public string $createdBy,
    ) {}
}
