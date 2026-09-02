<?php

namespace App\Application\Dispositivos\QueryHandlers;

use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Application\Dispositivos\Queries\GetDispositivosQuery;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class GetDispositivosQueryHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(GetDispositivosQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination, $query->user_id, $query->estado);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => DispositivoDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
