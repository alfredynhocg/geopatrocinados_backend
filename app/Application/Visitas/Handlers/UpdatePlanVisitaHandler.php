<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\UpdatePlanVisitaCommand;
use App\Application\Visitas\DTOs\PlanVisitaDTO;
use App\Domain\Visitas\Contracts\PlanVisitaRepositoryInterface;

class UpdatePlanVisitaHandler
{
    public function __construct(
        private readonly PlanVisitaRepositoryInterface $repository
    ) {}

    public function handle(UpdatePlanVisitaCommand $command): PlanVisitaDTO
    {
        $model = $this->repository->update($command->id, [
            'plan'         => $command->plan,
            'anio'         => $command->anio,
            'fecha_inicio' => $command->fechaInicio,
            'fecha_fin'    => $command->fechaFin,
            'estado'       => $command->estado,
            'updated_by'   => $command->updatedBy,
        ]);

        return PlanVisitaDTO::fromModel($model);
    }
}
