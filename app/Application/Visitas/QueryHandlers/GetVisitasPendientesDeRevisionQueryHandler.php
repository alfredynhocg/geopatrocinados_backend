<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetVisitasPendientesDeRevisionQuery;
use App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface;

class GetVisitasPendientesDeRevisionQueryHandler
{
    public function __construct(
        private readonly RevisionVisitaRepositoryInterface $repository
    ) {}

    public function handle(GetVisitasPendientesDeRevisionQuery $query): array
    {
        return $this->repository->pendientesDeRevision($query->pagination);
    }
}
