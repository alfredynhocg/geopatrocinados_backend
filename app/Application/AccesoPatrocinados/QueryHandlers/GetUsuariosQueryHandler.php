<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Application\AccesoPatrocinados\Queries\GetUsuariosQuery;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class GetUsuariosQueryHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(GetUsuariosQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => UsuarioDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
