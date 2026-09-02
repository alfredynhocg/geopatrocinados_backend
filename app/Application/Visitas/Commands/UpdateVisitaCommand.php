<?php

namespace App\Application\Visitas\Commands;

final readonly class UpdateVisitaCommand
{
    public function __construct(
        public string $id,
        public ?string $planVisitaId,
        public ?string $motivoVisitaId,
        public ?string $fechaProgramada,
    ) {}
    // Intencionalmente sin patrocinado_id/user_id/estado: user_id solo cambia vía
    // ReasignarVisitaHandler (6b), estado solo vía iniciar/finalizar/reprogramar (abajo).
}
