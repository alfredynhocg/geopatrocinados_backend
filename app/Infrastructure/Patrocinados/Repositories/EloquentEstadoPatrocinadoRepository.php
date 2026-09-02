<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;
use App\Domain\Patrocinados\Exceptions\EstadoPatrocinadoNotFoundException;
use App\Infrastructure\Patrocinados\Models\EstadoPatrocinado;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentEstadoPatrocinadoRepository implements EstadoPatrocinadoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $paginated = EstadoPatrocinado::query()
            ->whereNull('deleted_at')
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $estado = EstadoPatrocinado::whereNull('deleted_at')->find($id);

        if (! $estado) {
            throw new EstadoPatrocinadoNotFoundException($id);
        }

        return $estado;
    }

    public function create(array $data): mixed
    {
        return EstadoPatrocinado::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $estado = $this->findById($id);
        $estado->update($data);

        return $estado->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) EstadoPatrocinado::whereIn('id', (array) $ids)->delete();
    }
}
