<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;
use App\Domain\Patrocinados\Exceptions\TutorNotFoundException;
use App\Infrastructure\Patrocinados\Models\Tutor;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentTutorRepository implements TutorRepositoryInterface
{
    public function paginateByPatrocinado(string $patrocinadoId, PaginationDTO $pagination): array
    {
        $paginated = Tutor::query()
            ->where('patrocinado_id', $patrocinadoId)
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
        $tutor = Tutor::whereNull('deleted_at')->find($id);

        if (! $tutor) {
            throw new TutorNotFoundException($id);
        }

        return $tutor;
    }

    public function create(array $data): mixed
    {
        return Tutor::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $tutor = $this->findById($id);
        $tutor->update($data);

        return $tutor->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Tutor::whereIn('id', (array) $ids)->delete();
    }
}
