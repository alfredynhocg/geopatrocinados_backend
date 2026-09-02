<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\DeletePlanVisitaCommand;
use App\Domain\Visitas\Contracts\PlanVisitaRepositoryInterface;

class DeletePlanVisitaHandler
{
    public function __construct(
        private readonly PlanVisitaRepositoryInterface $repository
    ) {}

    public function handle(DeletePlanVisitaCommand $command): bool
    {
        return $this->repository->delete($command->ids);
    }
}
