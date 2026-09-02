<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CreateEstadoPatrocinadoCommand;
use App\Application\Patrocinados\DTOs\EstadoPatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;

class CreateEstadoPatrocinadoHandler
{
    public function __construct(private readonly EstadoPatrocinadoRepositoryInterface $repository) {}

    public function handle(CreateEstadoPatrocinadoCommand $command): EstadoPatrocinadoDTO
    {
        $model = $this->repository->create(['estado' => $command->estado]);

        return EstadoPatrocinadoDTO::fromModel($model);
    }
}
