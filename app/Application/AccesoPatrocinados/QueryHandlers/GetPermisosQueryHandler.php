<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\DTOs\PermisoDTO;
use App\Application\AccesoPatrocinados\Queries\GetPermisosQuery;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class GetPermisosQueryHandler
{
    public function __construct(private readonly PermisoRepositoryInterface $repository) {}

    public function handle(GetPermisosQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => PermisoDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
