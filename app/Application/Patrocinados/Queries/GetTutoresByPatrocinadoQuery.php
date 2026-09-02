<?php

namespace App\Application\Patrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetTutoresByPatrocinadoQuery
{
    public function __construct(
        public string $patrocinado_id,
        public PaginationDTO $pagination,
    ) {}
}
