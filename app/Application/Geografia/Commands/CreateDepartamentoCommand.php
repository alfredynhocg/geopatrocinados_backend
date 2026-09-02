<?php

namespace App\Application\Geografia\Commands;

final readonly class CreateDepartamentoCommand
{
    public function __construct(
        public ?string $codigo,
        public string $departamento,
        public bool $estado,
    ) {}
}
