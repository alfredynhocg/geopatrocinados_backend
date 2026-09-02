<?php

namespace App\Application\Visitas\DTOs;

final readonly class VisitaDTO
{
    public function __construct(
        public string $id,
        public ?string $planVisitaId,
        public string $patrocinadoId,
        public string $userId,
        public ?string $motivoVisitaId,
        public ?string $fechaProgramada,
        public ?string $fechaHabilitacion,
        public ?string $fechaInicio,
        public ?string $fechaFinalizacion,
        public string $estado,
        public string $estadoRevision,
        public string $estadoSincronizacion,
        public int $version,
        public int $intentosReprogramacion,
        public string $createdBy,
        public ?string $createdAt,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            planVisitaId: $model->plan_visita_id,
            patrocinadoId: $model->patrocinado_id,
            userId: $model->user_id,
            motivoVisitaId: $model->motivo_visita_id,
            fechaProgramada: $model->fecha_programada?->toDateString(),
            fechaHabilitacion: $model->fecha_habilitacion?->toIso8601String(),
            fechaInicio: $model->fecha_inicio?->toIso8601String(),
            fechaFinalizacion: $model->fecha_finalizacion?->toIso8601String(),
            estado: $model->estado,
            estadoRevision: $model->estado_revision,
            estadoSincronizacion: $model->estado_sincronizacion,
            version: (int) $model->version,
            intentosReprogramacion: (int) $model->intentos_reprogramacion,
            createdBy: $model->created_by,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
