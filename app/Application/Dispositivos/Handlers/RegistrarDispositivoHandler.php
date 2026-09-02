<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\RegistrarDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class RegistrarDispositivoHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(RegistrarDispositivoCommand $command): DispositivoDTO
    {
        $model = $this->repository->create([
            'user_id'                    => $command->user_id,
            'identificador_dispositivo'  => $command->identificador_dispositivo,
            'nombre_dispositivo'         => $command->nombre_dispositivo,
            'plataforma'                 => $command->plataforma,
            'version_sistema'            => $command->version_sistema,
            'version_aplicacion'         => $command->version_aplicacion,
            'estado'                     => 'PENDIENTE',
            'fecha_registro'             => now(),
        ]);

        return DispositivoDTO::fromModel($model);
    }
}
