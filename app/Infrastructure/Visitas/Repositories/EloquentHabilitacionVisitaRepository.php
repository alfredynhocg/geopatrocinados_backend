<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Domain\Visitas\Contracts\HabilitacionVisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\HabilitacionExpiradaException;
use App\Infrastructure\Visitas\Models\HabilitacionVisita;

class EloquentHabilitacionVisitaRepository implements HabilitacionVisitaRepositoryInterface
{
    public function findById(string $id): mixed
    {
        return HabilitacionVisita::find($id);
    }

    public function findActiva(string $visitaId, string $dispositivoId): mixed
    {
        return HabilitacionVisita::where('visita_id', $visitaId)
            ->where('dispositivo_id', $dispositivoId)
            ->where('estado', 'ACTIVA')
            ->first();
    }

    public function create(array $data): mixed
    {
        return HabilitacionVisita::create($data);
    }

    public function revocar(string $id, string $revokedBy): mixed
    {
        $model = HabilitacionVisita::find($id);
        if (! $model) {
            throw new HabilitacionExpiradaException($id);
        }

        $model->update([
            'estado'           => 'REVOCADA',
            'fecha_revocacion' => now(),
            'revoked_by'       => $revokedBy,
        ]);

        return $model->refresh();
    }
}
