<?php

namespace App\Application\Visitas\Commands;

final readonly class CreatePlanVisitaCommand
{
    public function __construct(
        public string $plan,
        public int $anio,
        public string $fechaInicio,
        public string $fechaFin,
        public string $createdBy,
    ) {}
}
