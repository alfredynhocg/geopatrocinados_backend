<?php

namespace App\Application\AccesoPatrocinados\QueryHandlers;

use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Application\AccesoPatrocinados\Queries\GetUsuarioByIdQuery;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class GetUsuarioByIdQueryHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(GetUsuarioByIdQuery $query): UsuarioDTO
    {
        return UsuarioDTO::fromModel($this->repository->findById($query->id));
    }
}
