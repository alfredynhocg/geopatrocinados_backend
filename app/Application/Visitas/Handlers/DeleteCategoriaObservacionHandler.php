<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\DeleteCategoriaObservacionCommand;
use App\Domain\Visitas\Contracts\CategoriaObservacionRepositoryInterface;

class DeleteCategoriaObservacionHandler
{
    public function __construct(
        private readonly CategoriaObservacionRepositoryInterface $repository
    ) {}

    public function handle(DeleteCategoriaObservacionCommand $command): bool
    {
        return $this->repository->delete($command->ids);
    }
}
