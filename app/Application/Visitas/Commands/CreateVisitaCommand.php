<?php

namespace App\Application\Visitas\Commands;

final readonly class CreateVisitaCommand
{
    public function __construct(
        public ?string $planVisitaId,
        public string $patrocinadoId,
        public string $userId,
        public ?string $motivoVisitaId,
        public ?string $fechaProgramada,
        public string $createdBy,
    ) {}
}
