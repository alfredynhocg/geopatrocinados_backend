<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\PlanVisitaDTO;
use App\Domain\Visitas\Contracts\PlanVisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\PlanVisitaNotFoundException;
use App\Infrastructure\Visitas\Models\PlanVisita;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentPlanVisitaRepository implements PlanVisitaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = PlanVisita::query();

        if ($pagination->query) {
            $q->where('plan', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?? 'anio', $pagination->sortOrder ?? 'desc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($m) => PlanVisitaDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        return PlanVisita::find($id);
    }

    public function create(array $data): mixed
    {
        return PlanVisita::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $model = PlanVisita::find($id);
        if (! $model) {
            throw new PlanVisitaNotFoundException($id);
        }
        $model->update($data);
        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) PlanVisita::destroy($ids);
    }
}
