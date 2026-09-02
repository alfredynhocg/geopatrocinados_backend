<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\DeleteMotivoVisitaCommand;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;

class DeleteMotivoVisitaHandler
{
    public function __construct(
        private readonly MotivoVisitaRepositoryInterface $repository
    ) {}

    public function handle(DeleteMotivoVisitaCommand $command): bool
    {
        return $this->repository->delete($command->ids);
    }
}
