<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\DTOs\DepartamentoDTO;
use App\Application\Geografia\Queries\GetDepartamentosQuery;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;

class GetDepartamentosQueryHandler
{
    public function __construct(private readonly DepartamentoRepositoryInterface $repository) {}

    public function handle(GetDepartamentosQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => DepartamentoDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
