<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\CreatePermisoCommand;
use App\Application\AccesoPatrocinados\DTOs\PermisoDTO;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class CreatePermisoHandler
{
    public function __construct(private readonly PermisoRepositoryInterface $repository) {}

    public function handle(CreatePermisoCommand $command): PermisoDTO
    {
        $model = $this->repository->create([
            'nombre'      => $command->nombre,
            'modulo'      => $command->modulo,
            'accion'      => $command->accion,
            'descripcion' => $command->descripcion,
        ]);

        return PermisoDTO::fromModel($model);
    }
}
