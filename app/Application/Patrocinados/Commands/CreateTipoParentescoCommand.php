<?php

namespace App\Application\Patrocinados\Commands;

final readonly class CreateTipoParentescoCommand
{
    public function __construct(
        public string $parentesco,
        public bool $estado,
    ) {}
}
