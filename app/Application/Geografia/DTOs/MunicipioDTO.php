<?php

namespace App\Application\Geografia\DTOs;

final readonly class MunicipioDTO
{
    public function __construct(
        public string $id,
        public string $departamento_id,
        public ?string $codigo,
        public string $municipio,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            departamento_id: $model->departamento_id,
            codigo: $model->codigo,
            municipio: $model->municipio,
            estado: (bool) $model->estado,
        );
    }
}
