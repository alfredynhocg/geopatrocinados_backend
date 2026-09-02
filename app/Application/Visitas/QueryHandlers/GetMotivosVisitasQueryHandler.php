<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetMotivosVisitasQuery;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;

class GetMotivosVisitasQueryHandler
{
    public function __construct(
        private readonly MotivoVisitaRepositoryInterface $repository
    ) {}

    public function handle(GetMotivosVisitasQuery $query): array
    {
        return $this->repository->paginate($query->pagination);
    }
}
