<?php

namespace App\Application\Geografia\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetMunicipiosQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $departamento_id = null,
    ) {}
}
