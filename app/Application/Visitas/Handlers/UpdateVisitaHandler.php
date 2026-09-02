<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\UpdateVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;

class UpdateVisitaHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $repository
    ) {}

    public function handle(UpdateVisitaCommand $command): VisitaDTO
    {
        $model = $this->repository->update($command->id, [
            'plan_visita_id'   => $command->planVisitaId,
            'motivo_visita_id' => $command->motivoVisitaId,
            'fecha_programada' => $command->fechaProgramada,
        ]);

        return VisitaDTO::fromModel($model);
    }
}
