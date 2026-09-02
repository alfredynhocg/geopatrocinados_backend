<?php

namespace App\Application\Visitas\Commands;

final readonly class HabilitarDispositivoParaVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $tecnicoId,
        public string $dispositivoId,
        public string $authorizedBy,
        public \DateTimeInterface $fechaExpiracion,
    ) {}
}
