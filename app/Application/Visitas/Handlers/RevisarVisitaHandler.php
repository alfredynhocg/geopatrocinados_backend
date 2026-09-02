<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\RevisarVisitaCommand;
use App\Application\Visitas\DTOs\RevisionVisitaDTO;
use App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

/** Implementa la regla de sincronía #4: revisiones_visitas y visitas.estado_revision se actualizan juntos. */
class RevisarVisitaHandler
{
    public function __construct(
        private readonly RevisionVisitaRepositoryInterface $revisionRepository,
        private readonly VisitaRepositoryInterface $visitaRepository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(RevisarVisitaCommand $command): RevisionVisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $model = $this->revisionRepository->create([
                'visita_id'            => $command->visitaId,
                'user_id'              => $command->userId,
                'fecha_revision'       => now(),
                'estado'               => $command->estado,
                'comentarios'          => $command->comentarios,
                'requiere_correccion'  => $command->estado === 'REQUIERE_CORRECCION',
            ]);

            $this->visitaRepository->actualizarEstadoRevision($command->visitaId, $command->estado);

            $this->auditoria->registrar(
                userId: $command->userId,
                dispositivoId: null,
                accion: 'revisar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: null,
                valoresNuevos: ['estado_revision' => $command->estado],
            );

            return RevisionVisitaDTO::fromModel($model);
        });
    }
}
