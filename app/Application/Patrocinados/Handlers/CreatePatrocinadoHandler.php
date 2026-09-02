<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CreatePatrocinadoCommand;
use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class CreatePatrocinadoHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    public function handle(CreatePatrocinadoCommand $command): PatrocinadoDTO
    {
        $model = $this->repository->create([
            'codigo'            => $command->codigo,
            'nombres'           => $command->nombres,
            'apellidos'         => $command->apellidos,
            'fecha_nacimiento'  => $command->fecha_nacimiento,
            'sexo'              => $command->sexo,
            'comunidad_id'      => $command->comunidad_id,
            'ubicacion_id'      => $command->ubicacion_id,
            'unidad_educativa'  => $command->unidad_educativa,
            'nivel_educativo'   => $command->nivel_educativo,
            'estado_id'         => $command->estado_id,
        ]);

        return PatrocinadoDTO::fromModel($model);
    }
}
