<?php

namespace App\Application\Geografia\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetUbicacionesQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $comunidad_id = null,
    ) {}
}
