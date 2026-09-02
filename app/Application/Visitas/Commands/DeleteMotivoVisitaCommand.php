<?php

namespace App\Application\Visitas\Commands;

final readonly class DeleteMotivoVisitaCommand
{
    public function __construct(public string|array $ids) {}
}
