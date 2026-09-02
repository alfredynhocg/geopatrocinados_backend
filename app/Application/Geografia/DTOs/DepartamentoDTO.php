<?php

namespace App\Application\Geografia\DTOs;

final readonly class DepartamentoDTO
{
    public function __construct(
        public string $id,
        public ?string $codigo,
        public string $departamento,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            codigo: $model->codigo,
            departamento: $model->departamento,
            estado: (bool) $model->estado,
        );
    }
}
