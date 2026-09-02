<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CreateTutorCommand;
use App\Application\Patrocinados\DTOs\TutorDTO;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;

class CreateTutorHandler
{
    public function __construct(private readonly TutorRepositoryInterface $repository) {}

    public function handle(CreateTutorCommand $command): TutorDTO
    {
        $model = $this->repository->create([
            'patrocinado_id'      => $command->patrocinado_id,
            'nombres'             => $command->nombres,
            'apellidos'           => $command->apellidos,
            'tipo_parentesco_id'  => $command->tipo_parentesco_id,
            'telefono'            => $command->telefono,
            'direccion'           => $command->direccion,
            'estado'              => $command->estado,
        ]);

        return TutorDTO::fromModel($model);
    }
}
