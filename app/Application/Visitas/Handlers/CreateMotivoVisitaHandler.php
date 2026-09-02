<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\CreateMotivoVisitaCommand;
use App\Application\Visitas\DTOs\MotivoVisitaDTO;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;

class CreateMotivoVisitaHandler
{
    public function __construct(
        private readonly MotivoVisitaRepositoryInterface $repository
    ) {}

    public function handle(CreateMotivoVisitaCommand $command): MotivoVisitaDTO
    {
        $model = $this->repository->create([
            'motivo_visita' => $command->motivoVisita,
            'descripcion'   => $command->descripcion,
            'estado'        => true,
            'updated_by'    => $command->updatedBy,
        ]);

        return MotivoVisitaDTO::fromModel($model);
    }
}
