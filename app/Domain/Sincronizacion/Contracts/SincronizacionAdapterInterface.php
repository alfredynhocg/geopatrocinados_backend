<?php

namespace App\Domain\Sincronizacion\Contracts;

/**
 * Contrato que debe implementar cada adapter concreto (VisitaSyncAdapter,
 * PatrocinadoSyncAdapter, ...) cuando el módulo de negocio correspondiente
 * esté implementado. SincronizacionRouterService solo conoce esta interfaz.
 */
interface SincronizacionAdapterInterface
{
    /**
     * @throws \App\Domain\Sincronizacion\Exceptions\ConflictoVersionException
     */
    public function procesar(string $operacion, string $entidadId, array $payload): void;
}
