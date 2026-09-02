<?php

namespace App\Application\Geografia\Commands;

final readonly class UpdateComunidadCommand
{
    public function __construct(
        public string $id,
        public string $municipio_id,
        public ?string $codigo,
        public string $comunidad,
        public bool $estado,
    ) {}
}
