<?php

namespace App\Application\Geografia\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetDepartamentosQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
