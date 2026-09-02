<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetRevisionesVisitaQuery;
use App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface;

class GetRevisionesVisitaQueryHandler
{
    public function __construct(
        private readonly RevisionVisitaRepositoryInterface $repository
    ) {}

    public function handle(GetRevisionesVisitaQuery $query): array
    {
        return $this->repository->listarPorVisita($query->visitaId);
    }
}
