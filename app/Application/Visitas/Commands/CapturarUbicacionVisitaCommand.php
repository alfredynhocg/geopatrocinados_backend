<?php

namespace App\Application\Visitas\Commands;

final readonly class CapturarUbicacionVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $dispositivoId,
        public string $tecnicoId,
        public float $latitude,
        public float $longitude,
        public ?float $precisionMetros,
        public string $fuente, // GPS | RED | MANUAL
    ) {}
}
