<?php

namespace App\Application\Patrocinados\Queries;

final readonly class GetPatrocinadoByIdQuery
{
    public function __construct(public string $id) {}
}
