<?php

namespace App\Application\Visitas\DTOs;

final readonly class PlanVisitaDTO
{
    public function __construct(
        public string $id,
        public string $plan,
        public int $anio,
        public string $fechaInicio,
        public string $fechaFin,
        public string $estado,
        public string $createdBy,
        public ?string $createdAt,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            plan: $model->plan,
            anio: (int) $model->anio,
            fechaInicio: $model->fecha_inicio?->toDateString(),
            fechaFin: $model->fecha_fin?->toDateString(),
            estado: $model->estado,
            createdBy: $model->created_by,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
