<?php

namespace App\Application\Patrocinados\Queries;

final readonly class GetTutorByIdQuery
{
    public function __construct(public string $id) {}
}
