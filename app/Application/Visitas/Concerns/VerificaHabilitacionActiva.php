<?php

namespace App\Application\Visitas\Concerns;

use App\Domain\Visitas\Exceptions\DispositivoNoHabilitadoException;
use App\Domain\Visitas\Exceptions\HabilitacionExpiradaException;
use App\Infrastructure\Visitas\Models\HabilitacionVisita;

/**
 * Usado por IniciarVisitaHandler y por los 3 Handlers de evidencia de campo (6c):
 * CapturarUbicacionVisitaHandler, CreateObservacionVisitaHandler, SubirFotoVisitaHandler.
 * Centraliza la regla de seguridad "todo dato de campo requiere habilitación ACTIVA
 * y no expirada" para no duplicar la query 4 veces.
 */
trait VerificaHabilitacionActiva
{
    protected function verificarHabilitacionActiva(string $visitaId, string $dispositivoId): HabilitacionVisita
    {
        $habilitacion = HabilitacionVisita::where('visita_id', $visitaId)
            ->where('dispositivo_id', $dispositivoId)
            ->where('estado', 'ACTIVA')
            ->first();

        if (! $habilitacion) {
            throw new DispositivoNoHabilitadoException($visitaId, $dispositivoId);
        }

        if ($habilitacion->fecha_expiracion->isPast()) {
            throw new HabilitacionExpiradaException($habilitacion->id);
        }

        return $habilitacion;
    }
}
