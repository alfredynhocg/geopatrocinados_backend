<?php

namespace App\Application\Geografia\Commands;

final readonly class UpdateDepartamentoCommand
{
    public function __construct(
        public string $id,
        public ?string $codigo,
        public string $departamento,
        public bool $estado,
    ) {}
}
