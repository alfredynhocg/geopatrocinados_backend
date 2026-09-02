<?php

namespace App\Application\Geografia\Commands;

final readonly class UpdateMunicipioCommand
{
    public function __construct(
        public string $id,
        public string $departamento_id,
        public ?string $codigo,
        public string $municipio,
        public bool $estado,
    ) {}
}
