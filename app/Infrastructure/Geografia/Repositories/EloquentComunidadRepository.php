<?php

namespace App\Infrastructure\Geografia\Repositories;

use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;
use App\Domain\Geografia\Exceptions\ComunidadNotFoundException;
use App\Infrastructure\Geografia\Models\Comunidad;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentComunidadRepository implements ComunidadRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $municipioId): array
    {
        $q = Comunidad::query();

        if ($municipioId) {
            $q->where('municipio_id', $municipioId);
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }

    public function findById(string $id): mixed
    {
        $comunidad = Comunidad::find($id);

        if (! $comunidad) {
            throw new ComunidadNotFoundException($id);
        }

        return $comunidad;
    }

    public function create(array $data): mixed
    {
        return Comunidad::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $comunidad = $this->findById($id);
        $comunidad->update($data);

        return $comunidad->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Comunidad::whereIn('id', (array) $ids)->delete();
    }
}
