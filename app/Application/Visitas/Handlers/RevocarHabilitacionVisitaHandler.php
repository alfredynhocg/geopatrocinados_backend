<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\RevocarHabilitacionVisitaCommand;
use App\Application\Visitas\DTOs\HabilitacionVisitaDTO;
use App\Domain\Visitas\Contracts\HabilitacionVisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * NO toca dispositivos.Estado (regla de sincronía documentada en 06-visitas.md #5):
 * revocar una habilitación puntual de visita es independiente del estado general
 * del dispositivo, que solo cambia vía Etapa 4 (RevocarDispositivoHandler).
 */
class RevocarHabilitacionVisitaHandler
{
    public function __construct(
        private readonly HabilitacionVisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(RevocarHabilitacionVisitaCommand $command): HabilitacionVisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $model = $this->repository->revocar($command->habilitacionId, $command->revokedBy);

            $this->auditoria->registrar(
                userId: $command->revokedBy,
                dispositivoId: $model->dispositivo_id,
                accion: 'revocar-habilitacion',
                modulo: 'Visitas',
                tipoEntidad: 'habilitacion_visita',
                entidadId: $command->habilitacionId,
                valoresAnteriores: ['estado' => 'ACTIVA'],
                valoresNuevos: ['estado' => 'REVOCADA'],
            );

            return HabilitacionVisitaDTO::fromModel($model);
        });
    }
}
