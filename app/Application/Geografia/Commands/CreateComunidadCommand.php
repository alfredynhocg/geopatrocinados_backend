<?php

namespace App\Application\Geografia\Commands;

final readonly class CreateComunidadCommand
{
    public function __construct(
        public string $municipio_id,
        public ?string $codigo,
        public string $comunidad,
        public bool $estado,
    ) {}
}
