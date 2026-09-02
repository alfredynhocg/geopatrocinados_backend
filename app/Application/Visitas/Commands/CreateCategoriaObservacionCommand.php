<?php

namespace App\Application\Visitas\Commands;

final readonly class CreateCategoriaObservacionCommand
{
    public function __construct(
        public string $codigo,
        public string $categoriaObservaciones,
        public ?string $descripcion,
        public string $updatedBy,
    ) {}
}
