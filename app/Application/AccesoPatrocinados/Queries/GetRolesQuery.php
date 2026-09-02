<?php

namespace App\Application\AccesoPatrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetRolesQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
