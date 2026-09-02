<?php

namespace App\Application\Patrocinados\Commands;

final readonly class DeletePatrocinadoCommand
{
    public function __construct(public string $id) {}
}
