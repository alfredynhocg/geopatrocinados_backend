<?php

namespace App\Application\Visitas\Commands;

final readonly class UpdateMotivoVisitaCommand
{
    public function __construct(
        public string $id,
        public string $motivoVisita,
        public ?string $descripcion,
        public bool $estado,
        public string $updatedBy,
    ) {}
}
