<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\DTOs\ComunidadDTO;
use App\Application\Geografia\Queries\GetComunidadesQuery;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;

class GetComunidadesQueryHandler
{
    public function __construct(private readonly ComunidadRepositoryInterface $repository) {}

    public function handle(GetComunidadesQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination, $query->municipio_id);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => ComunidadDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
