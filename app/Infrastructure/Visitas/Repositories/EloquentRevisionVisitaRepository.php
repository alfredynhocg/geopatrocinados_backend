<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\RevisionVisitaDTO;
use App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\RevisionVisita;
use App\Infrastructure\Visitas\Models\Visita;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentRevisionVisitaRepository implements RevisionVisitaRepositoryInterface
{
    public function create(array $data): mixed
    {
        return RevisionVisita::create($data);
    }

    public function listarPorVisita(string $visitaId): array
    {
        return RevisionVisita::where('visita_id', $visitaId)
            ->orderBy('fecha_revision', 'desc')
            ->get()
            ->map(fn ($m) => RevisionVisitaDTO::fromModel($m))
            ->all();
    }

    public function pendientesDeRevision(PaginationDTO $pagination): array
    {
        $paginated = Visita::where('estado_revision', 'PENDIENTE')
            ->where('estado', 'FINALIZADA')
            ->orderBy('fecha_finalizacion', 'asc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($v) => \App\Application\Visitas\DTOs\VisitaDTO::fromModel($v))->all(),
            'total' => $paginated->total(),
        ];
    }
}
