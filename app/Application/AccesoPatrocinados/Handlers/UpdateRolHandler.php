<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\UpdateRolCommand;
use App\Application\AccesoPatrocinados\DTOs\RolDTO;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class UpdateRolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(UpdateRolCommand $command): RolDTO
    {
        $model = $this->repository->update($command->id, [
            'nombre'      => $command->nombre,
            'descripcion' => $command->descripcion,
            'estado'      => $command->estado,
        ]);

        return RolDTO::fromModel($model);
    }
}
