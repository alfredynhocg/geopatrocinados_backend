<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;
use App\Domain\Patrocinados\Exceptions\PatrocinadoNotFoundException;
use App\Infrastructure\Patrocinados\Models\Patrocinado;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentPatrocinadoRepository implements PatrocinadoRepositoryInterface
{
    public function paginate(
        PaginationDTO $pagination,
        ?string $comunidadId,
        ?string $estadoId,
        ?string $nivelEducativo,
    ): array {
        $q = Patrocinado::query()->whereNull('deleted_at');

        if ($comunidadId) {
            $q->where('comunidad_id', $comunidadId);
        }
        if ($estadoId) {
            $q->where('estado_id', $estadoId);
        }
        if ($nivelEducativo) {
            $q->where('nivel_educativo', $nivelEducativo);
        }
        if ($pagination->query !== '') {
            $q->where(fn ($sub) => $sub
                ->where('codigo', 'ilike', "%{$pagination->query}%")
                ->orWhere('nombres', 'ilike', "%{$pagination->query}%")
                ->orWhere('apellidos', 'ilike', "%{$pagination->query}%"));
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $patrocinado = Patrocinado::with('tutores')->whereNull('deleted_at')->find($id);

        if (! $patrocinado) {
            throw new PatrocinadoNotFoundException($id);
        }

        return $patrocinado;
    }

    public function create(array $data): mixed
    {
        return Patrocinado::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $patrocinado = $this->findById($id);
        $patrocinado->update($data);

        return $patrocinado->fresh('tutores');
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Patrocinado::whereIn('id', (array) $ids)->delete();
    }

    public function moverUbicacion(string $patrocinadoId, string $comunidadId, ?string $ubicacionId): mixed
    {
        $patrocinado = $this->findById($patrocinadoId);

        $patrocinado->update([
            'comunidad_id' => $comunidadId,
            'ubicacion_id' => $ubicacionId,
        ]);

        return $patrocinado->fresh('tutores');
    }
}
