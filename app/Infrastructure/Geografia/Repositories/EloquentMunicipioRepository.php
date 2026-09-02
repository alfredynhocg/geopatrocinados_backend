<?php

namespace App\Infrastructure\Geografia\Repositories;

use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;
use App\Domain\Geografia\Exceptions\MunicipioNotFoundException;
use App\Infrastructure\Geografia\Models\Municipio;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentMunicipioRepository implements MunicipioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $departamentoId): array
    {
        $q = Municipio::query();

        if ($departamentoId) {
            $q->where('departamento_id', $departamentoId);
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }

    public function findById(string $id): mixed
    {
        $municipio = Municipio::find($id);

        if (! $municipio) {
            throw new MunicipioNotFoundException($id);
        }

        return $municipio;
    }

    public function create(array $data): mixed
    {
        return Municipio::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $municipio = $this->findById($id);
        $municipio->update($data);

        return $municipio->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Municipio::whereIn('id', (array) $ids)->delete();
    }
}
