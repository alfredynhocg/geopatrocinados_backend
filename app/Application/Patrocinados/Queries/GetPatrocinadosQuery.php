<?php

namespace App\Application\Patrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetPatrocinadosQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $comunidad_id = null,
        public ?string $estado_id = null,
        public ?string $nivel_educativo = null,
    ) {}
}
