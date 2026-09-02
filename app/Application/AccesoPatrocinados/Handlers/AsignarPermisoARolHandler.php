<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\AsignarPermisoARolCommand;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class AsignarPermisoARolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(AsignarPermisoARolCommand $command): void
    {
        $rol = $this->repository->findById($command->rol_id);

        $rol->permisos()->syncWithoutDetaching([$command->permiso_id]);
    }
}
