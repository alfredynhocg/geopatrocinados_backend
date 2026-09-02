<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetPlanesVisitaQuery;
use App\Domain\Visitas\Contracts\PlanVisitaRepositoryInterface;

class GetPlanesVisitaQueryHandler
{
    public function __construct(
        private readonly PlanVisitaRepositoryInterface $repository
    ) {}

    public function handle(GetPlanesVisitaQuery $query): array
    {
        return $this->repository->paginate($query->pagination);
    }
}
