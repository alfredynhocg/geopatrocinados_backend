<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\DTOs\MunicipioDTO;
use App\Application\Geografia\Queries\GetMunicipiosQuery;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;

class GetMunicipiosQueryHandler
{
    public function __construct(private readonly MunicipioRepositoryInterface $repository) {}

    public function handle(GetMunicipiosQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination, $query->departamento_id);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => MunicipioDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
