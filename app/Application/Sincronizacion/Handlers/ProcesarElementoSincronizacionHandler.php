<?php

namespace App\Application\Sincronizacion\Handlers;

use App\Application\Sincronizacion\Commands\ProcesarElementoSincronizacionCommand;
use App\Application\Sincronizacion\DTOs\ElementoSincronizacionDTO;
use App\Application\Sincronizacion\Services\SincronizacionRouterService;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Domain\Sincronizacion\Exceptions\ConflictoVersionException;
use Illuminate\Support\Facades\DB;

/**
 * Cada elemento es su propia unidad transaccional — un fallo en uno no
 * aborta el resto del lote. Idempotente por tipo_entidad+entidad_id+hash_datos.
 */
class ProcesarElementoSincronizacionHandler
{
    public function __construct(
        private readonly ElementoSincronizacionRepositoryInterface $repository,
        private readonly SincronizacionRouterService $router,
    ) {}

    public function handle(ProcesarElementoSincronizacionCommand $command): ElementoSincronizacionDTO
    {
        $existente = $this->repository->findSincronizadoPorEntidadYHash(
            $command->tipo_entidad,
            $command->entidad_id,
            $command->hash_datos,
        );

        if ($existente !== null) {
            return ElementoSincronizacionDTO::fromModel($existente);
        }

        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $elemento = $this->repository->create([
                'lote_sincronizacion_id' => $command->lote_id,
                'tipo_entidad'           => $command->tipo_entidad,
                'entidad_id'             => $command->entidad_id,
                'operacion'              => $command->operacion,
                'hash_datos'             => $command->hash_datos,
                'estado'                 => 'PENDIENTE',
            ]);

            try {
                $this->router->despachar($command->tipo_entidad, $command->operacion, $command->entidad_id, $command->payload);

                $elemento = $this->repository->marcarSincronizado($elemento->id);
            } catch (ConflictoVersionException) {
                $elemento = $this->repository->marcarError($elemento->id, 'conflicto_version');
            } catch (\Throwable $e) {
                $elemento = $this->repository->marcarError($elemento->id, $e->getMessage());
            }

            return ElementoSincronizacionDTO::fromModel($elemento);
        });
    }
}
