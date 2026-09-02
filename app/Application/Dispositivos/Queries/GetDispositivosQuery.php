<?php

namespace App\Application\Dispositivos\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetDispositivosQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $user_id = null,
        public ?string $estado = null,
    ) {}
}
