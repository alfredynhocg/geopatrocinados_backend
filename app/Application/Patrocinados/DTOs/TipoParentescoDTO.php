<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class TipoParentescoDTO
{
    public function __construct(
        public string $id,
        public string $parentesco,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            parentesco: $model->parentesco,
            estado: (bool) $model->estado,
        );
    }
}
