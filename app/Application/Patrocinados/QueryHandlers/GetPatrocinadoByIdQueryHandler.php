<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Application\Patrocinados\DTOs\PatrocinadoResumenDTO;
use App\Application\Patrocinados\Queries\GetPatrocinadoByIdQuery;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class GetPatrocinadoByIdQueryHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    public function handle(GetPatrocinadoByIdQuery $query): PatrocinadoDTO|PatrocinadoResumenDTO
    {
        $model = $this->repository->findById($query->id);

        $conDetalle = (bool) auth()->user()?->can('patrocinados.ver-detalle');

        return $conDetalle ? PatrocinadoDTO::fromModel($model) : PatrocinadoResumenDTO::fromModel($model);
    }
}
