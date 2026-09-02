<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class HistorialUbicacionDTO
{
    public function __construct(
        public string $id,
        public string $patrocinado_id,
        public string $comunidad_id,
        public ?string $ubicacion_id,
        public string $fecha_inicio,
        public ?string $fecha_fin,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            patrocinado_id: $model->patrocinado_id,
            comunidad_id: $model->comunidad_id,
            ubicacion_id: $model->ubicacion_id,
            fecha_inicio: $model->fecha_inicio->toDateString(),
            fecha_fin: $model->fecha_fin?->toDateString(),
        );
    }
}
