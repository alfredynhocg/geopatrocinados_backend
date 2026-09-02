<?php

namespace App\Application\Visitas\DTOs;

final readonly class RevisionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public string $userId,
        public string $fechaRevision,
        public string $estado,
        public ?string $comentarios,
        public bool $requiereCorreccion,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            userId: $model->user_id,
            fechaRevision: $model->fecha_revision?->toIso8601String(),
            estado: $model->estado,
            comentarios: $model->comentarios,
            requiereCorreccion: (bool) $model->requiere_correccion,
        );
    }
}
