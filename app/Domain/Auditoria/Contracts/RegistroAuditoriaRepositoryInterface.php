<?php

namespace App\Domain\Auditoria\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface RegistroAuditoriaRepositoryInterface
{
    public function create(array $data): mixed;

    public function paginate(
        PaginationDTO $pagination,
        ?string $tipoEntidad,
        ?string $entidadId,
        ?string $userId,
        ?string $desde,
        ?string $hasta,
    ): array;
}
