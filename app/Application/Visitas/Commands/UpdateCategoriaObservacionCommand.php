<?php

namespace App\Application\Visitas\Commands;

final readonly class UpdateCategoriaObservacionCommand
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $categoriaObservaciones,
        public ?string $descripcion,
        public bool $estado,
        public string $updatedBy,
    ) {}
}
