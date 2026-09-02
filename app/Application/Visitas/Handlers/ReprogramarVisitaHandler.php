<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\ReprogramarVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Implementación mínima viable — la regla completa de reprogramación (docs/patrocinados/06-visitas.md,
 * "Decisiones de negocio a cerrar" #1) está pendiente de cierre con negocio:
 * cuántos ciclos exactos, quién decide la baja final. Este Handler cubre el camino feliz
 * (reprogramar a 3 meses) y deja el camino de "agotó reprogramaciones" delegado a un
 * Command separado (DarDeBajaPatrocinadoPorNoUbicadoCommand) en vez de decidirlo aquí mismo.
 */
class ReprogramarVisitaHandler
{
    private const MAX_REPROGRAMACIONES = 2; // pendiente de confirmación de negocio

    public function __construct(
        private readonly VisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(ReprogramarVisitaCommand $command): VisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $anterior = $this->repository->findById($command->visitaId);

            if ($anterior->intentos_reprogramacion >= self::MAX_REPROGRAMACIONES) {
                throw new \RuntimeException(
                    'Se agotaron los intentos de reprogramación. Debe decidirse la baja del patrocinado '
                    . 'vía DarDeBajaPatrocinadoPorNoUbicadoCommand (requiere decisión de un supervisor).',
                    422
                );
            }

            $model = $this->repository->actualizarEstado($command->visitaId, [
                'estado'                  => 'REPROGRAMADA',
                'fecha_programada'        => now()->addMonths(3)->toDateString(),
                'intentos_reprogramacion' => $anterior->intentos_reprogramacion + 1,
            ]);

            $this->auditoria->registrar(
                userId: $command->ejecutadoPor,
                dispositivoId: null,
                accion: 'reprogramar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: ['estado' => $anterior->estado, 'intentos_reprogramacion' => $anterior->intentos_reprogramacion],
                valoresNuevos: ['estado' => 'REPROGRAMADA', 'intentos_reprogramacion' => $anterior->intentos_reprogramacion + 1],
            );

            return VisitaDTO::fromModel($model);
        });
    }
}
