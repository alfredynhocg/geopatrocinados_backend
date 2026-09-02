<?php

namespace App\Domain\Servicios\Contracts;

use App\Application\Servicios\DTOs\ServicioDTO;
use App\Shared\Kernel\DTOs\PaginationDTO;

interface ServicioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $categoria = null, bool $soloDestacados = false): array;

    public function findById(int $id): ServicioDTO;

    public function findBySlug(string $slug): ServicioDTO;

    public function create(array $data): ServicioDTO;

    public function update(int $id, array $data): ServicioDTO;

    public function delete(int|array $ids): bool;
}
