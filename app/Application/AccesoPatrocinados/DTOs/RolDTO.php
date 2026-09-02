<?php

namespace App\Application\AccesoPatrocinados\DTOs;

final readonly class RolDTO
{
    public function __construct(
        public string $id,
        public string $nombre,
        public ?string $descripcion,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            nombre: $model->nombre,
            descripcion: $model->descripcion,
            estado: (bool) $model->estado,
        );
    }
}
