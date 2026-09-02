<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\AsignarRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class AsignarRolHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(AsignarRolCommand $command): void
    {
        $usuario = $this->repository->findById($command->usuario_id);

        $usuario->roles()->syncWithoutDetaching([
            $command->rol_id => ['updated_by' => $command->asignado_por],
        ]);
    }
}
