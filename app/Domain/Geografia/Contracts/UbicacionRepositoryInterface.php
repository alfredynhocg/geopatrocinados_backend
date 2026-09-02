<?php

namespace App\Domain\Geografia\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface UbicacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $comunidadId): array;

    public function findById(string $id): mixed;

    /** Recalcula punto_geografico a partir de latitude/longitude — ver nota de módulo. */
    public function create(array $data): mixed;

    /** Recalcula punto_geografico a partir de latitude/longitude — ver nota de módulo. */
    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
