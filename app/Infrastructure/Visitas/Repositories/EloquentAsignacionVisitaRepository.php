<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Domain\Visitas\Contracts\AsignacionVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\AsignacionVisita;

class EloquentAsignacionVisitaRepository implements AsignacionVisitaRepositoryInterface
{
    public function findActivaPorVisita(string $visitaId): mixed
    {
        return AsignacionVisita::where('visita_id', $visitaId)->where('estado', true)->first();
    }

    public function cerrarActiva(string $visitaId): void
    {
        AsignacionVisita::where('visita_id', $visitaId)
            ->where('estado', true)
            ->update(['estado' => false, 'fecha_desasignacion' => now()]);
    }

    public function create(array $data): mixed
    {
        return AsignacionVisita::create($data);
    }
}
