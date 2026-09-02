<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\DeleteComunidadCommand;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;

class DeleteComunidadHandler
{
    public function __construct(private readonly ComunidadRepositoryInterface $repository) {}

    public function handle(DeleteComunidadCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
