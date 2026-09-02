<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\FinalizarVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

class FinalizarVisitaHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(FinalizarVisitaCommand $command): VisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $anterior = $this->repository->findById($command->visitaId);

            $model = $this->repository->actualizarEstado($command->visitaId, [
                'estado'              => $command->estadoFinal,
                'fecha_finalizacion'  => now(),
            ]);

            $this->auditoria->registrar(
                userId: $command->ejecutadoPor,
                dispositivoId: null,
                accion: 'finalizar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: ['estado' => $anterior->estado],
                valoresNuevos: ['estado' => $command->estadoFinal],
            );

            return VisitaDTO::fromModel($model);
        });
    }
}
