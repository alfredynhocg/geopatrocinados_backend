<?php

namespace App\Application\Visitas\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetVisitasPendientesDeRevisionQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
