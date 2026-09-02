<?php

namespace App\Application\Sincronizacion\Services;

/**
 * Mapa tipo_entidad => adapter concreto. Los adapters (VisitaSyncAdapter,
 * PatrocinadoSyncAdapter, etc.) se implementan cuando el módulo
 * correspondiente esté listo — este Service solo define el contrato de
 * enrutamiento, no inventa la lógica de negocio de cada adapter.
 */
class SincronizacionRouterService
{
    private const MAPA = [
        // 'visita'      => \App\Application\Visitas\Sincronizacion\VisitaSyncAdapter::class,
        // 'patrocinado' => \App\Application\Patrocinados\Sincronizacion\PatrocinadoSyncAdapter::class,
        // 'observacion' => \App\Application\Visitas\Sincronizacion\ObservacionVisitaSyncAdapter::class,
        // 'foto'        => \App\Application\Visitas\Sincronizacion\FotoVisitaSyncAdapter::class,
        // 'ubicacion'   => \App\Application\Visitas\Sincronizacion\UbicacionVisitaSyncAdapter::class,
    ];

    public function despachar(string $tipoEntidad, string $operacion, string $entidadId, array $payload): void
    {
        if (! isset(self::MAPA[$tipoEntidad])) {
            throw new \InvalidArgumentException("Tipo de entidad de sincronización sin adapter registrado: {$tipoEntidad}");
        }

        /** @var \App\Domain\Sincronizacion\Contracts\SincronizacionAdapterInterface $adapter */
        $adapter = app()->make(self::MAPA[$tipoEntidad]);
        $adapter->procesar($operacion, $entidadId, $payload);
    }
}
