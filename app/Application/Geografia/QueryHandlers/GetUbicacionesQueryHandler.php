<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\DTOs\UbicacionDTO;
use App\Application\Geografia\Queries\GetUbicacionesQuery;
use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;

class GetUbicacionesQueryHandler
{
    public function __construct(private readonly UbicacionRepositoryInterface $repository) {}

    public function handle(GetUbicacionesQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination, $query->comunidad_id);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => UbicacionDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
