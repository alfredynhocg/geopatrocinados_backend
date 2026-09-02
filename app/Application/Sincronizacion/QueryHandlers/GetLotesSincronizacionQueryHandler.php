<?php

namespace App\Application\Sincronizacion\QueryHandlers;

use App\Application\Sincronizacion\DTOs\LoteSincronizacionDTO;
use App\Application\Sincronizacion\Queries\GetLotesSincronizacionQuery;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;

class GetLotesSincronizacionQueryHandler
{
    public function __construct(private readonly LoteSincronizacionRepositoryInterface $repository) {}

    public function handle(GetLotesSincronizacionQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination, $query->dispositivo_id, $query->estado);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => LoteSincronizacionDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
