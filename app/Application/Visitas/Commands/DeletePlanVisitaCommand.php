<?php

namespace App\Application\Visitas\Commands;

final readonly class DeletePlanVisitaCommand
{
    public function __construct(public string|array $ids) {}
}
