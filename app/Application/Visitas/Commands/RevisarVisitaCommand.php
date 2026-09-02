<?php

namespace App\Application\Visitas\Commands;

final readonly class RevisarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $userId,
        public string $estado, // APROBADA | RECHAZADA | REQUIERE_CORRECCION
        public ?string $comentarios,
    ) {}
}
