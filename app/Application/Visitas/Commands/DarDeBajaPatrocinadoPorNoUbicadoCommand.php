<?php

namespace App\Application\Visitas\Commands;

final readonly class DarDeBajaPatrocinadoPorNoUbicadoCommand
{
    public function __construct(
        public string $patrocinadoId,
        public string $visitaId,
        public string $decididoPor,
        public ?string $comentario,
    ) {}
}
