<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\EstadoPatrocinadoDTO;
use App\Application\Patrocinados\Queries\GetEstadosPatrocinadoQuery;
use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;

class GetEstadosPatrocinadoQueryHandler
{
    public function __construct(private readonly EstadoPatrocinadoRepositoryInterface $repository) {}

    public function handle(GetEstadosPatrocinadoQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => EstadoPatrocinadoDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
