<?php

namespace App\Application\Sincronizacion\QueryHandlers;

use App\Application\Sincronizacion\DTOs\ElementoSincronizacionDTO;
use App\Application\Sincronizacion\Queries\GetElementosPendientesQuery;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;

class GetElementosPendientesQueryHandler
{
    public function __construct(private readonly ElementoSincronizacionRepositoryInterface $repository) {}

    public function handle(GetElementosPendientesQuery $query): array
    {
        $elementos = $this->repository->listPendientesByLote($query->lote_id);

        return array_map(fn (object $m) => ElementoSincronizacionDTO::fromModel($m), $elementos);
    }
}
