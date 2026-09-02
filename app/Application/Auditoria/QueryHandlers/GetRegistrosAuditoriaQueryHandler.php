<?php

namespace App\Application\Auditoria\QueryHandlers;

use App\Application\Auditoria\DTOs\RegistroAuditoriaDTO;
use App\Application\Auditoria\Queries\GetRegistrosAuditoriaQuery;
use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;

class GetRegistrosAuditoriaQueryHandler
{
    public function __construct(private readonly RegistroAuditoriaRepositoryInterface $repository) {}

    public function handle(GetRegistrosAuditoriaQuery $query): array
    {
        $paginated = $this->repository->paginate(
            $query->pagination,
            $query->tipo_entidad,
            $query->entidad_id,
            $query->user_id,
            $query->desde,
            $query->hasta,
        );

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => RegistroAuditoriaDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
