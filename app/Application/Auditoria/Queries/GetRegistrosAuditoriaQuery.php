<?php

namespace App\Application\Auditoria\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetRegistrosAuditoriaQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $tipo_entidad = null,
        public ?string $entidad_id = null,
        public ?string $user_id = null,
        public ?string $desde = null,
        public ?string $hasta = null,
    ) {}
}
