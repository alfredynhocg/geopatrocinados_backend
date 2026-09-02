<?php

namespace App\Application\Patrocinados\Commands;

final readonly class UpdateTipoParentescoCommand
{
    public function __construct(
        public string $id,
        public string $parentesco,
        public bool $estado,
    ) {}
}
