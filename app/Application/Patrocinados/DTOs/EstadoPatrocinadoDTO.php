<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class EstadoPatrocinadoDTO
{
    public function __construct(
        public string $id,
        public string $estado,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            estado: $model->estado,
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
