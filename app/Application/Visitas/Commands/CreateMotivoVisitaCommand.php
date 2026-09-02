<?php

namespace App\Application\Visitas\Commands;

final readonly class CreateMotivoVisitaCommand
{
    public function __construct(
        public string $motivoVisita,
        public ?string $descripcion,
        public string $updatedBy,
    ) {}
}
