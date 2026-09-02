<?php

namespace App\Infrastructure\Sincronizacion\Repositories;

use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Infrastructure\Sincronizacion\Models\ElementoSincronizacion;

class EloquentElementoSincronizacionRepository implements ElementoSincronizacionRepositoryInterface
{
    public function findSincronizadoPorEntidadYHash(string $tipoEntidad, string $entidadId, ?string $hashDatos): mixed
    {
        return ElementoSincronizacion::query()
            ->where('tipo_entidad', $tipoEntidad)
            ->where('entidad_id', $entidadId)
            ->where('hash_datos', $hashDatos)
            ->where('estado', 'SINCRONIZADO')
            ->first();
    }

    public function create(array $data): mixed
    {
        return ElementoSincronizacion::create($data);
    }

    public function marcarSincronizado(string $id): mixed
    {
        $elemento = ElementoSincronizacion::findOrFail($id);
        $elemento->update([
            'estado'                => 'SINCRONIZADO',
            'fecha_sincronizacion'  => now(),
        ]);

        return $elemento->fresh();
    }

    public function marcarError(string $id, string $mensajeError): mixed
    {
        $elemento = ElementoSincronizacion::findOrFail($id);
        $elemento->update([
            'estado'         => 'ERROR',
            'intentos'       => $elemento->intentos + 1,
            'mensaje_error'  => $mensajeError,
        ]);

        return $elemento->fresh();
    }

    public function listPendientesByLote(string $loteId): array
    {
        return ElementoSincronizacion::where('lote_sincronizacion_id', $loteId)->get()->all();
    }
}
