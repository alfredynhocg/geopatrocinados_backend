<?php

namespace App\Application\Geografia\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetComunidadesQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $municipio_id = null,
    ) {}
}
