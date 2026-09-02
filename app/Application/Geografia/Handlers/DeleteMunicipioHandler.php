<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\DeleteMunicipioCommand;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;

class DeleteMunicipioHandler
{
    public function __construct(private readonly MunicipioRepositoryInterface $repository) {}

    public function handle(DeleteMunicipioCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
