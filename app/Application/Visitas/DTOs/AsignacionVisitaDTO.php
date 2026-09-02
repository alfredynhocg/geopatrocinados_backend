<?php

namespace App\Application\Visitas\DTOs;

final readonly class AsignacionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public string $tecnicoId,
        public string $assignedBy,
        public string $fechaAsignacion,
        public ?string $fechaDesasignacion,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            tecnicoId: $model->tecnico_id,
            assignedBy: $model->assigned_by,
            fechaAsignacion: $model->fecha_asignacion?->toIso8601String(),
            fechaDesasignacion: $model->fecha_desasignacion?->toIso8601String(),
            estado: (bool) $model->estado,
        );
    }
}
