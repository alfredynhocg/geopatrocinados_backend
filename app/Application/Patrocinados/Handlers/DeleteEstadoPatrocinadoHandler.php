<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\DeleteEstadoPatrocinadoCommand;
use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;

class DeleteEstadoPatrocinadoHandler
{
    public function __construct(private readonly EstadoPatrocinadoRepositoryInterface $repository) {}

    public function handle(DeleteEstadoPatrocinadoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
