<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\UpdatePermisoCommand;
use App\Application\AccesoPatrocinados\DTOs\PermisoDTO;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class UpdatePermisoHandler
{
    public function __construct(private readonly PermisoRepositoryInterface $repository) {}

    public function handle(UpdatePermisoCommand $command): PermisoDTO
    {
        $model = $this->repository->update($command->id, [
            'nombre'      => $command->nombre,
            'modulo'      => $command->modulo,
            'accion'      => $command->accion,
            'descripcion' => $command->descripcion,
        ]);

        return PermisoDTO::fromModel($model);
    }
}
