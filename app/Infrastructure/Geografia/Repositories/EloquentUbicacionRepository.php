<?php

namespace App\Infrastructure\Geografia\Repositories;

use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;
use App\Domain\Geografia\Exceptions\UbicacionNotFoundException;
use App\Infrastructure\Geografia\Models\Ubicacion;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Support\Facades\DB;

/**
 * Única fuente de verdad de la derivación lat/lng -> punto_geografico
 * (plan de revisión §5.6). ST_MakePoint espera (longitude, latitude).
 */
class EloquentUbicacionRepository implements UbicacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $comunidadId): array
    {
        $q = Ubicacion::query();

        if ($comunidadId) {
            $q->where('comunidad_id', $comunidadId);
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }

    public function findById(string $id): mixed
    {
        $ubicacion = Ubicacion::find($id);

        if (! $ubicacion) {
            throw new UbicacionNotFoundException($id);
        }

        return $ubicacion;
    }

    public function create(array $data): mixed
    {
        $ubicacion = Ubicacion::create($data);

        $this->recalcularPunto($ubicacion->id, $data['longitude'], $data['latitude']);

        return $ubicacion->fresh();
    }

    public function update(string $id, array $data): mixed
    {
        $ubicacion = $this->findById($id);
        $ubicacion->update($data);

        $this->recalcularPunto($ubicacion->id, $data['longitude'], $data['latitude']);

        return $ubicacion->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Ubicacion::whereIn('id', (array) $ids)->delete();
    }

    private function recalcularPunto(string $id, float $longitude, float $latitude): void
    {
        DB::connection('pgsql_patrocinados')->statement(
            'UPDATE ubicaciones SET punto_geografico = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$longitude, $latitude, $id],
        );
    }
}
