<?php

namespace App\Application\Visitas\Commands;

final readonly class UpdatePlanVisitaCommand
{
    public function __construct(
        public string $id,
        public string $plan,
        public int $anio,
        public string $fechaInicio,
        public string $fechaFin,
        public string $estado,
        public string $updatedBy,
    ) {}
}
