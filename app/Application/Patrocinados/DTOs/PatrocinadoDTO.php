<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class PatrocinadoDTO
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $nombres,
        public ?string $apellidos,
        public ?string $fecha_nacimiento,
        public ?string $sexo,
        public string $comunidad_id,
        public ?string $ubicacion_id,
        public ?string $unidad_educativa,
        public ?string $nivel_educativo,
        public string $estado_id,
        public array $tutores,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            codigo: $model->codigo,
            nombres: $model->nombres,
            apellidos: $model->apellidos,
            fecha_nacimiento: $model->fecha_nacimiento?->toDateString(),
            sexo: $model->sexo,
            comunidad_id: $model->comunidad_id,
            ubicacion_id: $model->ubicacion_id,
            unidad_educativa: $model->unidad_educativa,
            nivel_educativo: $model->nivel_educativo,
            estado_id: $model->estado_id,
            tutores: $model->relationLoaded('tutores')
                ? $model->tutores->map(fn (object $tutor) => TutorDTO::fromModel($tutor))->all()
                : [],
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
