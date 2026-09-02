<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\UbicacionVisitaDTO;
use App\Domain\Visitas\Contracts\UbicacionVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\UbicacionVisita;
use Illuminate\Support\Facades\DB;

/** Única fuente de verdad de la derivación lat/lng -> punto_geografico (regla §5.6). */
class EloquentUbicacionVisitaRepository implements UbicacionVisitaRepositoryInterface
{
    public function create(array $data): mixed
    {
        $model = UbicacionVisita::create($data);

        DB::connection('pgsql_patrocinados')->statement(
            'UPDATE ubicaciones_visitas SET punto_geografico = ST_MakePoint(?, ?)::geography WHERE id = ?',
            [$data['longitude'], $data['latitude'], $model->id]
        );

        return $model->refresh();
    }

    public function listarPorVisita(string $visitaId): array
    {
        return UbicacionVisita::where('visita_id', $visitaId)
            ->orderBy('fecha_captura', 'desc')
            ->get()
            ->map(fn ($m) => UbicacionVisitaDTO::fromModel($m))
            ->all();
    }
}
