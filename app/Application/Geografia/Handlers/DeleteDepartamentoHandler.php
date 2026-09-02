<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\DeleteDepartamentoCommand;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;

class DeleteDepartamentoHandler
{
    public function __construct(private readonly DepartamentoRepositoryInterface $repository) {}

    public function handle(DeleteDepartamentoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
