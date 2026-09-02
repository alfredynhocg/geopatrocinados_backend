<?php

namespace App\Domain\Visitas\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface RevisionVisitaRepositoryInterface
{
    public function create(array $data): mixed;
    public function listarPorVisita(string $visitaId): array;
    public function pendientesDeRevision(PaginationDTO $pagination): array;
}
