<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\UpdateMotivoVisitaCommand;
use App\Application\Visitas\DTOs\MotivoVisitaDTO;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;

class UpdateMotivoVisitaHandler
{
    public function __construct(
        private readonly MotivoVisitaRepositoryInterface $repository
    ) {}

    public function handle(UpdateMotivoVisitaCommand $command): MotivoVisitaDTO
    {
        $model = $this->repository->update($command->id, [
            'motivo_visita' => $command->motivoVisita,
            'descripcion'   => $command->descripcion,
            'estado'        => $command->estado,
            'updated_by'    => $command->updatedBy,
        ]);

        return MotivoVisitaDTO::fromModel($model);
    }
}
