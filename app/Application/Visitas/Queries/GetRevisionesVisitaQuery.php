<?php

namespace App\Application\Visitas\Queries;

final readonly class GetRevisionesVisitaQuery
{
    public function __construct(public string $visitaId) {}
}
