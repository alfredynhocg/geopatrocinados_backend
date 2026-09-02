<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;
use App\Infrastructure\Patrocinados\Models\TipoParentesco;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentTipoParentescoRepository implements TipoParentescoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $paginated = TipoParentesco::query()
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        return TipoParentesco::findOrFail($id);
    }

    public function create(array $data): mixed
    {
        return TipoParentesco::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $tipo = $this->findById($id);
        $tipo->update($data);

        return $tipo->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) TipoParentesco::whereIn('id', (array) $ids)->delete();
    }
}
