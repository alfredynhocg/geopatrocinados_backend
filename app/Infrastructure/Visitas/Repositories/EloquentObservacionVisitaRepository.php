<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\ObservacionVisitaDTO;
use App\Domain\Visitas\Contracts\ObservacionVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\ObservacionVisita;

class EloquentObservacionVisitaRepository implements ObservacionVisitaRepositoryInterface
{
    public function create(array $data): mixed
    {
        return ObservacionVisita::create($data);
    }

    public function listarPorVisita(string $visitaId): array
    {
        return ObservacionVisita::where('visita_id', $visitaId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($m) => ObservacionVisitaDTO::fromModel($m))
            ->all();
    }
}
