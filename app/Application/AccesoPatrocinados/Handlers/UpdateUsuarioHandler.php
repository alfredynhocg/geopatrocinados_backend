<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\UpdateUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class UpdateUsuarioHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(UpdateUsuarioCommand $command): UsuarioDTO
    {
        $model = $this->repository->update($command->id, [
            'nombres'   => $command->nombres,
            'apellidos' => $command->apellidos,
            'telefono'  => $command->telefono,
            'estado'    => $command->estado,
        ]);

        return UsuarioDTO::fromModel($model);
    }
}
