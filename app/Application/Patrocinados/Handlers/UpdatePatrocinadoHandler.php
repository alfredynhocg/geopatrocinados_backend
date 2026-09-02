<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\UpdatePatrocinadoCommand;
use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class UpdatePatrocinadoHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    /**
     * No recibe ni puede recibir comunidad_id/ubicacion_id: el Command no
     * declara esas propiedades. Para mover a un patrocinado usar
     * CambiarUbicacionPatrocinadoHandler.
     */
    public function handle(UpdatePatrocinadoCommand $command): PatrocinadoDTO
    {
        $model = $this->repository->update($command->id, [
            'nombres'           => $command->nombres,
            'apellidos'         => $command->apellidos,
            'fecha_nacimiento'  => $command->fecha_nacimiento,
            'sexo'              => $command->sexo,
            'unidad_educativa'  => $command->unidad_educativa,
            'nivel_educativo'   => $command->nivel_educativo,
            'estado_id'         => $command->estado_id,
        ]);

        return PatrocinadoDTO::fromModel($model);
    }
}
