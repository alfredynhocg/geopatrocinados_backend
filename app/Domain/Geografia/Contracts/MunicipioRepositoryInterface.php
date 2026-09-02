<?php

namespace App\Domain\Geografia\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface MunicipioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $departamentoId): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
