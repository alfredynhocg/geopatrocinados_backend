<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\CreateVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateVisitaHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(CreateVisitaCommand $command): VisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $model = $this->repository->create([
                'plan_visita_id'   => $command->planVisitaId,
                'patrocinado_id'   => $command->patrocinadoId,
                'user_id'          => $command->userId,
                'motivo_visita_id' => $command->motivoVisitaId,
                'fecha_programada' => $command->fechaProgramada,
                'estado'           => 'PLANIFICADA',
                'created_by'       => $command->createdBy,
            ]);

            $this->auditoria->registrar(
                userId: $command->createdBy,
                dispositivoId: null,
                accion: 'crear',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $model->id,
                valoresAnteriores: null,
                valoresNuevos: ['patrocinado_id' => $command->patrocinadoId, 'user_id' => $command->userId, 'estado' => 'PLANIFICADA'],
            );

            return VisitaDTO::fromModel($model);
        });
    }
}
