<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class TutorDTO
{
    public function __construct(
        public string $id,
        public string $patrocinado_id,
        public string $nombres,
        public string $apellidos,
        public string $tipo_parentesco_id,
        public ?string $telefono,
        public string $direccion,
        public bool $estado,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            patrocinado_id: $model->patrocinado_id,
            nombres: $model->nombres,
            apellidos: $model->apellidos,
            tipo_parentesco_id: $model->tipo_parentesco_id,
            telefono: $model->telefono,
            direccion: $model->direccion,
            estado: (bool) $model->estado,
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
