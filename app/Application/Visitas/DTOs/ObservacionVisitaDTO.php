<?php

namespace App\Application\Visitas\DTOs;

final readonly class ObservacionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public ?string $categoriaId,
        public string $tipo,
        public string $observacion,
        public string $createdBy,
        public ?string $createdAt,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            categoriaId: $model->categoria_id,
            tipo: $model->tipo,
            observacion: $model->observacion,
            createdBy: $model->created_by,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
