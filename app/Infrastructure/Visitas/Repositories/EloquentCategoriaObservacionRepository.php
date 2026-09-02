<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\CategoriaObservacionDTO;
use App\Domain\Visitas\Contracts\CategoriaObservacionRepositoryInterface;
use App\Domain\Visitas\Exceptions\CategoriaObservacionNotFoundException;
use App\Infrastructure\Visitas\Models\CategoriaObservacion;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentCategoriaObservacionRepository implements CategoriaObservacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = CategoriaObservacion::query();

        if ($pagination->query) {
            $q->where('categoria_observaciones', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?? 'categoria_observaciones', $pagination->sortOrder ?? 'asc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($m) => CategoriaObservacionDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        return CategoriaObservacion::find($id);
    }

    public function create(array $data): mixed
    {
        return CategoriaObservacion::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $model = CategoriaObservacion::find($id);
        if (! $model) {
            throw new CategoriaObservacionNotFoundException($id);
        }
        $model->update($data);
        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) CategoriaObservacion::destroy($ids);
    }
}
