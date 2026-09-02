<?php

namespace App\Application\Visitas\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetPlanesVisitaQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
