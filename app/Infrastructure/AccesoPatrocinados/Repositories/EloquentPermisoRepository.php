<?php

namespace App\Infrastructure\AccesoPatrocinados\Repositories;

use App\Domain\AccesoPatrocinados\Contracts\PermisoRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\PermisoNotFoundException;
use App\Infrastructure\AccesoPatrocinados\Models\Permiso;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentPermisoRepository implements PermisoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $paginated = Permiso::query()
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $permiso = Permiso::find($id);

        if (! $permiso) {
            throw new PermisoNotFoundException($id);
        }

        return $permiso;
    }

    public function create(array $data): mixed
    {
        return Permiso::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $permiso = $this->findById($id);
        $permiso->update($data);

        return $permiso->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Permiso::whereIn('id', (array) $ids)->delete();
    }
}
