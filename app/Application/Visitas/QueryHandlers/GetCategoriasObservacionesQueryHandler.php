<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetCategoriasObservacionesQuery;
use App\Domain\Visitas\Contracts\CategoriaObservacionRepositoryInterface;

class GetCategoriasObservacionesQueryHandler
{
    public function __construct(
        private readonly CategoriaObservacionRepositoryInterface $repository
    ) {}

    public function handle(GetCategoriasObservacionesQuery $query): array
    {
        return $this->repository->paginate($query->pagination);
    }
}
