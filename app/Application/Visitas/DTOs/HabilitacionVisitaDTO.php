<?php

namespace App\Application\Visitas\DTOs;

final readonly class HabilitacionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public string $tecnicoId,
        public string $dispositivoId,
        public string $authorizedBy,
        public string $fechaHabilitacion,
        public string $fechaExpiracion,
        public string $estado,
        public ?string $fechaRevocacion,
        public ?string $revokedBy,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            tecnicoId: $model->tecnico_id,
            dispositivoId: $model->dispositivo_id,
            authorizedBy: $model->authorized_by,
            fechaHabilitacion: $model->fecha_habilitacion?->toIso8601String(),
            fechaExpiracion: $model->fecha_expiracion?->toIso8601String(),
            estado: $model->estado,
            fechaRevocacion: $model->fecha_revocacion?->toIso8601String(),
            revokedBy: $model->revoked_by,
        );
    }
}
