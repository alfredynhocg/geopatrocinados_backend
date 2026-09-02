<?php

namespace App\Application\Visitas\Commands;

final readonly class DeleteCategoriaObservacionCommand
{
    public function __construct(public string|array $ids) {}
}
