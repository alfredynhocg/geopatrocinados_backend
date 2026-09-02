<?php

namespace App\Domain\Sincronizacion\Contracts;

interface ElementoSincronizacionRepositoryInterface
{
    /** Para idempotencia de reenvío: null si no existe un elemento ya SINCRONIZADO con ese hash. */
    public function findSincronizadoPorEntidadYHash(string $tipoEntidad, string $entidadId, ?string $hashDatos): mixed;

    public function create(array $data): mixed;

    public function marcarSincronizado(string $id): mixed;

    public function marcarError(string $id, string $mensajeError): mixed;

    public function listPendientesByLote(string $loteId): array;
}
