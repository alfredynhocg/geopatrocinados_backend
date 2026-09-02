<?php

namespace App\Domain\Patrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface EstadoPatrocinadoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
