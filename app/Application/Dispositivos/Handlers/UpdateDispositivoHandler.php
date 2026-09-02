<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\UpdateDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class UpdateDispositivoHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(UpdateDispositivoCommand $command): DispositivoDTO
    {
        $model = $this->repository->update($command->id, [
            'nombre_dispositivo' => $command->nombre_dispositivo,
            'version_sistema'    => $command->version_sistema,
            'version_aplicacion' => $command->version_aplicacion,
        ]);

        return DispositivoDTO::fromModel($model);
    }
}
