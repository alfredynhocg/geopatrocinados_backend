<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\IniciarVisitaCommand;
use App\Application\Visitas\Concerns\VerificaHabilitacionActiva;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

class IniciarVisitaHandler
{
    use VerificaHabilitacionActiva;

    public function __construct(
        private readonly VisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(IniciarVisitaCommand $command): VisitaDTO
    {
        $this->verificarHabilitacionActiva($command->visitaId, $command->dispositivoId);

        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $anterior = $this->repository->findById($command->visitaId);

            $model = $this->repository->actualizarEstado($command->visitaId, [
                'estado'       => 'EN_CURSO',
                'fecha_inicio' => now(),
            ]);

            $this->auditoria->registrar(
                userId: $command->ejecutadoPor,
                dispositivoId: $command->dispositivoId,
                accion: 'iniciar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: ['estado' => $anterior->estado],
                valoresNuevos: ['estado' => 'EN_CURSO'],
            );

            return VisitaDTO::fromModel($model);
        });
    }
}
