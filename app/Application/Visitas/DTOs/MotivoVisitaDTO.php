<?php

namespace App\Application\Visitas\DTOs;

final readonly class MotivoVisitaDTO
{
    public function __construct(
        public string $id,
        public string $motivoVisita,
        public ?string $descripcion,
        public bool $estado,
        public ?string $createdAt,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            motivoVisita: $model->motivo_visita,
            descripcion: $model->descripcion,
            estado: (bool) $model->estado,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
