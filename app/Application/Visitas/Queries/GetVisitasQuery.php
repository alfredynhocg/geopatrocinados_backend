<?php

namespace App\Application\Visitas\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetVisitasQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $patrocinadoId = null,
        public ?string $tecnicoId = null,
        public ?string $estado = null,
        public ?string $desde = null,
        public ?string $hasta = null,
    ) {}
}
