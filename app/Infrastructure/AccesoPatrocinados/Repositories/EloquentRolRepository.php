<?php

namespace App\Infrastructure\AccesoPatrocinados\Repositories;

use App\Domain\AccesoPatrocinados\Contracts\RolRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\RolNotFoundException;
use App\Infrastructure\AccesoPatrocinados\Models\Rol;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentRolRepository implements RolRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $paginated = Rol::query()
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $rol = Rol::with('permisos')->find($id);

        if (! $rol) {
            throw new RolNotFoundException($id);
        }

        return $rol;
    }

    public function create(array $data): mixed
    {
        return Rol::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $rol = $this->findById($id);
        $rol->update($data);

        return $rol->fresh('permisos');
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Rol::whereIn('id', (array) $ids)->delete();
    }
}
