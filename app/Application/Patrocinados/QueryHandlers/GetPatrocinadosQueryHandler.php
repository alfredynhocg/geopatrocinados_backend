<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Application\Patrocinados\DTOs\PatrocinadoResumenDTO;
use App\Application\Patrocinados\Queries\GetPatrocinadosQuery;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class GetPatrocinadosQueryHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    public function handle(GetPatrocinadosQuery $query): array
    {
        $paginated = $this->repository->paginate(
            $query->pagination,
            $query->comunidad_id,
            $query->estado_id,
            $query->nivel_educativo,
        );

        // Dato sensible de menor de edad: sin el permiso, siempre el resumen.
        $conDetalle = (bool) auth()->user()?->can('patrocinados.ver-detalle');

        return [
            'data' => collect($paginated['data'])
                ->map(fn (object $model) => $conDetalle
                    ? PatrocinadoDTO::fromModel($model)
                    : PatrocinadoResumenDTO::fromModel($model))
                ->all(),
            'total' => $paginated['total'],
        ];
    }
}
