<?php

namespace App\Application\Sincronizacion\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetLotesSincronizacionQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $dispositivo_id = null,
        public ?string $estado = null,
    ) {}
}
