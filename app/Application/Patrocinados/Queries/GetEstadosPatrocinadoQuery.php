<?php

namespace App\Application\Patrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetEstadosPatrocinadoQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
