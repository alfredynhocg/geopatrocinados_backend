<?php

namespace App\Application\Dispositivos\Handlers;

use App\Application\Dispositivos\Commands\AprobarDispositivoCommand;
use App\Application\Dispositivos\DTOs\DispositivoDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;

class AprobarDispositivoHandler
{
    public function __construct(private readonly DispositivoRepositoryInterface $repository) {}

    public function handle(AprobarDispositivoCommand $command): DispositivoDTO
    {
        $model = $this->repository->aprobar($command->id);

        // TODO: registrar en registros_auditoria vía AuditoriaService cuando la Etapa 8 esté implementada.

        return DispositivoDTO::fromModel($model);
    }
}
