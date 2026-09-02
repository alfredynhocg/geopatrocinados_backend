<?php

namespace App\Application\AccesoPatrocinados\DTOs;

final readonly class PermisoDTO
{
    public function __construct(
        public string $id,
        public string $nombre,
        public string $modulo,
        public string $accion,
        public ?string $descripcion,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            nombre: $model->nombre,
            modulo: $model->modulo,
            accion: $model->accion,
            descripcion: $model->descripcion,
        );
    }
}
