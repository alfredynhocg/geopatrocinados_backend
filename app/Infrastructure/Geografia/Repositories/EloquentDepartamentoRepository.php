<?php

namespace App\Infrastructure\Geografia\Repositories;

use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;
use App\Domain\Geografia\Exceptions\DepartamentoNotFoundException;
use App\Infrastructure\Geografia\Models\Departamento;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentDepartamentoRepository implements DepartamentoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $paginated = Departamento::query()
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }

    public function findById(string $id): mixed
    {
        $departamento = Departamento::find($id);

        if (! $departamento) {
            throw new DepartamentoNotFoundException($id);
        }

        return $departamento;
    }

    public function create(array $data): mixed
    {
        return Departamento::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $departamento = $this->findById($id);
        $departamento->update($data);

        return $departamento->fresh();
    }

    public function delete(string|array $ids): bool
    {
        // FK sin cascade (onDelete('restrict')) — Postgres rechaza el delete
        // si hay municipios asociados, lo que se propaga como QueryException.
        return (bool) Departamento::whereIn('id', (array) $ids)->delete();
    }
}
