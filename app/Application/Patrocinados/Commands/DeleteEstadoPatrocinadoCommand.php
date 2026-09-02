<?php

namespace App\Application\Patrocinados\Commands;

final readonly class DeleteEstadoPatrocinadoCommand
{
    public function __construct(public string $id) {}
}
