<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\DeletePatrocinadoCommand;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class DeletePatrocinadoHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    public function handle(DeletePatrocinadoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
