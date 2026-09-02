<?php

namespace App\Application\Dispositivos\QueryHandlers;

use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Application\Dispositivos\Queries\GetDispositivoByIdQuery;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class GetDispositivoByIdQueryHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(GetDispositivoByIdQuery $query): DispositivoDTO
    {
        return DispositivoDTO::fromModel($this->repository->findById($query->id));
    }
}
