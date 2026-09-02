<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\DeleteRolCommand;
use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;

class DeleteRolHandler
{
    public function __construct(private readonly RolRepositoryInterface $repository) {}

    public function handle(DeleteRolCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
