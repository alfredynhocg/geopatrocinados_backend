<?php

namespace App\Application\Patrocinados\Commands;

final readonly class CreateEstadoPatrocinadoCommand
{
    public function __construct(public string $estado) {}
}
