<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\FotoVisitaDTO;
use App\Domain\Visitas\Contracts\FotoVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\FotoVisita;

class EloquentFotoVisitaRepository implements FotoVisitaRepositoryInterface
{
    public function create(array $data): mixed
    {
        return FotoVisita::create($data);
    }

    public function findById(string $id): mixed
    {
        return FotoVisita::find($id);
    }

    public function listarPorVisita(string $visitaId): array
    {
        return FotoVisita::where('visita_id', $visitaId)
            ->orderBy('fecha_captura', 'desc')
            ->get()
            ->map(fn ($m) => FotoVisitaDTO::fromModel($m))
            ->all();
    }
}
