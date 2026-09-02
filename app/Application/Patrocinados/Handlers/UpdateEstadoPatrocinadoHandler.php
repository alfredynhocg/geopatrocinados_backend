<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\UpdateEstadoPatrocinadoCommand;
use App\Application\Patrocinados\DTOs\EstadoPatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;

class UpdateEstadoPatrocinadoHandler
{
    public function __construct(private readonly EstadoPatrocinadoRepositoryInterface $repository) {}

    public function handle(UpdateEstadoPatrocinadoCommand $command): EstadoPatrocinadoDTO
    {
        $model = $this->repository->update($command->id, ['estado' => $command->estado]);

        return EstadoPatrocinadoDTO::fromModel($model);
    }
}
