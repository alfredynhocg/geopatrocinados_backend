<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\DeleteUsuarioCommand;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;

class DeleteUsuarioHandler
{
    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    public function handle(DeleteUsuarioCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
