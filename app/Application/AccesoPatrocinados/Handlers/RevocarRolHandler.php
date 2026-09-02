<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\RevocarRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class RevocarRolHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(RevocarRolCommand $command): void
    {
        $usuario = $this->repository->findById($command->usuario_id);

        $usuario->roles()->detach($command->rol_id);
    }
}
