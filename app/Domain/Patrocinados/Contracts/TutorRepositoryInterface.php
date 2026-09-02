<?php

namespace App\Domain\Patrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface TutorRepositoryInterface
{
    public function paginateByPatrocinado(string $patrocinadoId, PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
