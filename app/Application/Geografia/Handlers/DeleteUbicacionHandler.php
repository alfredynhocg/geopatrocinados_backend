<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\DeleteUbicacionCommand;
use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;

class DeleteUbicacionHandler
{
    public function __construct(private readonly UbicacionRepositoryInterface $repository) {}

    public function handle(DeleteUbicacionCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
