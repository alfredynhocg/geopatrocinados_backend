<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\HabilitarDispositivoParaVisitaCommand;
use App\Application\Visitas\DTOs\HabilitacionVisitaDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Visitas\Contracts\HabilitacionVisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\DispositivoNoHabilitadoException;
use Illuminate\Support\Facades\DB;

class HabilitarDispositivoParaVisitaHandler
{
    public function __construct(
        private readonly HabilitacionVisitaRepositoryInterface $repository,
        private readonly DispositivoRepositoryInterface $dispositivoRepository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(HabilitarDispositivoParaVisitaCommand $command): HabilitacionVisitaDTO
    {
        $dispositivo = $this->dispositivoRepository->findById($command->dispositivoId);
        if (! $dispositivo || $dispositivo->estado === 'REVOCADO') {
            // NUNCA se toca dispositivos.Estado desde este Handler — solo se lee para validar.
            throw new DispositivoNoHabilitadoException('(pendiente)', $command->dispositivoId);
        }

        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $model = $this->repository->create([
                'visita_id'         => $command->visitaId,
                'tecnico_id'        => $command->tecnicoId,
                'dispositivo_id'    => $command->dispositivoId,
                'authorized_by'     => $command->authorizedBy,
                'fecha_habilitacion'=> now(),
                'fecha_expiracion'  => $command->fechaExpiracion,
                'estado'            => 'ACTIVA',
            ]);

            $this->auditoria->registrar(
                userId: $command->authorizedBy,
                dispositivoId: $command->dispositivoId,
                accion: 'habilitar-dispositivo',
                modulo: 'Visitas',
                tipoEntidad: 'habilitacion_visita',
                entidadId: $model->id,
                valoresAnteriores: null,
                valoresNuevos: ['visita_id' => $command->visitaId, 'dispositivo_id' => $command->dispositivoId, 'estado' => 'ACTIVA'],
            );

            return HabilitacionVisitaDTO::fromModel($model);
        });
    }
}
