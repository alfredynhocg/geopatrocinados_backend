<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetVisitasQuery;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;

class GetVisitasQueryHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $repository
    ) {}

    public function handle(GetVisitasQuery $query): array
    {
        return $this->repository->paginate($query->pagination, [
            'patrocinado_id' => $query->patrocinadoId,
            'tecnico_id'     => $query->tecnicoId,
            'estado'         => $query->estado,
            'desde'          => $query->desde,
            'hasta'          => $query->hasta,
        ]);
    }
}
