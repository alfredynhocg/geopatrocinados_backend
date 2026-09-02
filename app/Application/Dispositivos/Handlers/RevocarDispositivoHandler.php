<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\RevocarDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class RevocarDispositivoHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(RevocarDispositivoCommand $command): DispositivoDTO
    {
        $model = $this->repository->revocar($command->id, $command->revoked_by);

        // TODO: registrar en registros_auditoria vía AuditoriaService cuando la Etapa 8 esté implementada.

        return DispositivoDTO::fromModel($model);
    }
}
