<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\DTOs\RolDTO;
use App\Application\AccesoPatrocinados\Queries\GetRolesQuery;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class GetRolesQueryHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(GetRolesQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => RolDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
