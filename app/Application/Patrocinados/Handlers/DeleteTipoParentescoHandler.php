<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\DeleteTipoParentescoCommand;
use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;

class DeleteTipoParentescoHandler
{
    public function __construct(private readonly TipoParentescoRepositoryInterface $repository) {}

    public function handle(DeleteTipoParentescoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
