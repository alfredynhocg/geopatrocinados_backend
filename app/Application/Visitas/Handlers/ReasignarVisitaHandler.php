<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\ReasignarVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\AsignacionVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\VisitaYaAsignadaException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReasignarVisitaHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $visitaRepository,
        private readonly AsignacionVisitaRepositoryInterface $asignacionRepository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(ReasignarVisitaCommand $command): VisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $visitaAnterior = $this->visitaRepository->findById($command->visitaId);

            // Chequeo explícito antes del insert: evita depender de capturar la
            // violación del índice único parcial como único mecanismo de control.
            if ($this->visitaRepository->existeAsignacionActiva($command->visitaId)) {
                $this->asignacionRepository->cerrarActiva($command->visitaId);
            }

            try {
                $this->asignacionRepository->create([
                    'visita_id'         => $command->visitaId,
                    'tecnico_id'        => $command->nuevoTecnicoId,
                    'assigned_by'       => $command->assignedBy,
                    'fecha_asignacion'  => now(),
                    'estado'            => true,
                ]);
            } catch (QueryException $e) {
                if (str_contains($e->getMessage(), 'uq_asignaciones_visitas_activa')) {
                    throw new VisitaYaAsignadaException($command->visitaId);
                }
                throw $e;
            }

            $modelVisita = $this->visitaRepository->reasignarTecnico($command->visitaId, $command->nuevoTecnicoId);

            $this->auditoria->registrar(
                userId: $command->assignedBy,
                dispositivoId: null,
                accion: 'reasignar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: ['user_id' => $visitaAnterior->user_id],
                valoresNuevos: ['user_id' => $command->nuevoTecnicoId],
            );

            return VisitaDTO::fromModel($modelVisita);
        });
    }
}
