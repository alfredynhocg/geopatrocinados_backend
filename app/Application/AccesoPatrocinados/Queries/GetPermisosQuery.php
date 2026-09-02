<?php

namespace App\Application\AccesoPatrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetPermisosQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
