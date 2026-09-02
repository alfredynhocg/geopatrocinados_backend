<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\DeletePermisoCommand;
use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;

class DeletePermisoHandler
{
    public function __construct(private readonly PermisoRepositoryInterface $repository) {}

    public function handle(DeletePermisoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
