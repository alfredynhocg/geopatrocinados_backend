<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\DeleteTutorCommand;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;

class DeleteTutorHandler
{
    public function __construct(private readonly TutorRepositoryInterface $repository) {}

    public function handle(DeleteTutorCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
