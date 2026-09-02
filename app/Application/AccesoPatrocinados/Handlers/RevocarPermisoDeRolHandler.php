<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\RevocarPermisoDeRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class RevocarPermisoDeRolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(RevocarPermisoDeRolCommand $command): void
    {
        $rol = $this->repository->findById($command->rol_id);

        $rol->permisos()->detach($command->permiso_id);
    }
}
