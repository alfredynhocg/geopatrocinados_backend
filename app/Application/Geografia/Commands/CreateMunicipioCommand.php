<?php

namespace App\Application\Geografia\Commands;

final readonly class CreateMunicipioCommand
{
    public function __construct(
        public string $departamento_id,
        public ?string $codigo,
        public string $municipio,
        public bool $estado,
    ) {}
}
