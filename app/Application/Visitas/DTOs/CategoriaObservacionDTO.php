<?php

namespace App\Application\Visitas\DTOs;

final readonly class CategoriaObservacionDTO
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $categoriaObservaciones,
        public ?string $descripcion,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            codigo: $model->codigo,
            categoriaObservaciones: $model->categoria_observaciones,
            descripcion: $model->descripcion,
            estado: (bool) $model->estado,
        );
    }
}
