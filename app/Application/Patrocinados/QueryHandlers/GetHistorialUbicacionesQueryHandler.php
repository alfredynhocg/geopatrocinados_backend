<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\HistorialUbicacionDTO;
use App\Application\Patrocinados\Queries\GetHistorialUbicacionesQuery;
use App\Domain\Patrocinados\Contracts\HistorialUbicacionRepositoryInterface;

class GetHistorialUbicacionesQueryHandler
{
    public function __construct(private readonly HistorialUbicacionRepositoryInterface $repository) {}

    public function handle(GetHistorialUbicacionesQuery $query): array
    {
        $rows = $this->repository->listByPatrocinado($query->patrocinado_id);

        return array_map(fn (object $model) => HistorialUbicacionDTO::fromModel($model), $rows);
    }
}
