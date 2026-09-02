<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\HistorialUbicacionRepositoryInterface;
use App\Infrastructure\Patrocinados\Models\HistorialUbicacion;

class EloquentHistorialUbicacionRepository implements HistorialUbicacionRepositoryInterface
{
    public function listByPatrocinado(string $patrocinadoId): array
    {
        return HistorialUbicacion::where('patrocinado_id', $patrocinadoId)
            ->orderByDesc('fecha_inicio')
            ->get()
            ->all();
    }

    public function findAbiertoByPatrocinado(string $patrocinadoId): mixed
    {
        return HistorialUbicacion::where('patrocinado_id', $patrocinadoId)
            ->whereNull('fecha_fin')
            ->first();
    }

    public function cerrar(string $id): void
    {
        HistorialUbicacion::whereKey($id)->update(['fecha_fin' => now()->toDateString()]);
    }

    public function create(array $data): mixed
    {
        return HistorialUbicacion::create($data);
    }
}
