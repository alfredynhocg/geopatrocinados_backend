<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\TipoParentescoDTO;
use App\Application\Patrocinados\Queries\GetTiposParentescoQuery;
use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;

class GetTiposParentescoQueryHandler
{
    public function __construct(private readonly TipoParentescoRepositoryInterface $repository) {}

    public function handle(GetTiposParentescoQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => TipoParentescoDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
