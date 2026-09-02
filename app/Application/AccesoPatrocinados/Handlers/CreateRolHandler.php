<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\CreateRolCommand;
use App\Application\AccesoPatrocinados\DTOs\RolDTO;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class CreateRolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(CreateRolCommand $command): RolDTO
    {
        $model = $this->repository->create([
            'nombre'      => $command->nombre,
            'descripcion' => $command->descripcion,
            'estado'      => $command->estado,
        ]);

        return RolDTO::fromModel($model);
    }
}
