<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CambiarUbicacionPatrocinadoCommand;
use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\HistorialUbicacionRepositoryInterface;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Único camino válido para cambiar comunidad_id/ubicacion_id de un
 * patrocinado. Implementa la regla de sincronía patrocinados <-> historial_ubicaciones
 * (plan de revisión §8.1): cierra la fila vigente, abre la nueva, y
 * actualiza el "estado actual" en patrocinados, todo en la misma transacción.
 */
class CambiarUbicacionPatrocinadoHandler
{
    public function __construct(
        private readonly PatrocinadoRepositoryInterface $patrocinadoRepository,
        private readonly HistorialUbicacionRepositoryInterface $historialRepository,
    ) {}

    public function handle(CambiarUbicacionPatrocinadoCommand $command): PatrocinadoDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $abierta = $this->historialRepository->findAbiertoByPatrocinado($command->patrocinado_id);

            if ($abierta !== null) {
                $this->historialRepository->cerrar($abierta->id);
            }

            $this->historialRepository->create([
                'patrocinado_id' => $command->patrocinado_id,
                'comunidad_id'   => $command->comunidad_id,
                'ubicacion_id'   => $command->ubicacion_id,
                'fecha_inicio'   => now()->toDateString(),
                'fecha_fin'      => null,
                'created_by'     => $command->usuario_id,
            ]);

            $patrocinado = $this->patrocinadoRepository->moverUbicacion(
                $command->patrocinado_id,
                $command->comunidad_id,
                $command->ubicacion_id,
            );

            return PatrocinadoDTO::fromModel($patrocinado);
        });
    }
}
