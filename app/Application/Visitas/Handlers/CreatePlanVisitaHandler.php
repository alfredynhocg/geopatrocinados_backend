<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\CreatePlanVisitaCommand;
use App\Application\Visitas\DTOs\PlanVisitaDTO;
use App\Domain\Visitas\Contracts\PlanVisitaRepositoryInterface;

class CreatePlanVisitaHandler
{
    public function __construct(
        private readonly PlanVisitaRepositoryInterface $repository
    ) {}

    public function handle(CreatePlanVisitaCommand $command): PlanVisitaDTO
    {
        $model = $this->repository->create([
            'plan'         => $command->plan,
            'anio'         => $command->anio,
            'fecha_inicio' => $command->fechaInicio,
            'fecha_fin'    => $command->fechaFin,
            'estado'       => 'ACTIVO',
            'created_by'   => $command->createdBy,
        ]);

        return PlanVisitaDTO::fromModel($model);
    }
}
