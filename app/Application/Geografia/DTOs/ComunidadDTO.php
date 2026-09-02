<?php

namespace App\Application\Geografia\DTOs;

final readonly class ComunidadDTO
{
    public function __construct(
        public string $id,
        public string $municipio_id,
        public ?string $codigo,
        public string $comunidad,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            municipio_id: $model->municipio_id,
            codigo: $model->codigo,
            comunidad: $model->comunidad,
            estado: (bool) $model->estado,
        );
    }
}
