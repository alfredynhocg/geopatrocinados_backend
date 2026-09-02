<?php

namespace App\Application\Visitas\Queries;

final readonly class GetVisitaByIdQuery
{
    public function __construct(public string $id) {}
}
