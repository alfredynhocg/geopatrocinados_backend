<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\MotivoVisitaDTO;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\MotivoVisitaNotFoundException;
use App\Infrastructure\Visitas\Models\MotivoVisita;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentMotivoVisitaRepository implements MotivoVisitaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = MotivoVisita::query();

        if ($pagination->query) {
            $q->where('motivo_visita', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?? 'motivo_visita', $pagination->sortOrder ?? 'asc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($m) => MotivoVisitaDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        return MotivoVisita::find($id);
    }

    public function create(array $data): mixed
    {
        return MotivoVisita::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $model = MotivoVisita::find($id);
        if (! $model) {
            throw new MotivoVisitaNotFoundException($id);
        }
        $model->update($data);
        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) MotivoVisita::destroy($ids);
    }
}
