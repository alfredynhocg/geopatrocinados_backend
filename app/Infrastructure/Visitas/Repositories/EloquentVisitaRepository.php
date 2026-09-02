<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\VisitaNotFoundException;
use App\Infrastructure\Visitas\Models\Visita;
use App\Shared\Kernel\DTOs\PaginationDTO;

/**
 * Único Repository de todo el módulo Visitas con métodos de escritura no genéricos
 * (reasignarTecnico, actualizarEstado, actualizarEstadoRevision) — deliberado: son
 * los únicos caminos válidos para tocar user_id / estado / estado_revision, según
 * las reglas de sincronía documentadas en docs/patrocinados/06-visitas.md.
 */
class EloquentVisitaRepository implements VisitaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, array $filtros = []): array
    {
        $q = Visita::query()->with(['asignacionActiva', 'habilitacionActiva']);

        if (! empty($filtros['patrocinado_id'])) {
            $q->where('patrocinado_id', $filtros['patrocinado_id']);
        }
        if (! empty($filtros['tecnico_id'])) {
            $q->where('user_id', $filtros['tecnico_id']);
        }
        if (! empty($filtros['estado'])) {
            $q->where('estado', $filtros['estado']);
        }
        if (! empty($filtros['desde'])) {
            $q->whereDate('fecha_programada', '>=', $filtros['desde']);
        }
        if (! empty($filtros['hasta'])) {
            $q->whereDate('fecha_programada', '<=', $filtros['hasta']);
        }

        $paginated = $q->orderBy('fecha_programada', 'desc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($v) => \App\Application\Visitas\DTOs\VisitaDTO::fromModel($v))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        return Visita::with(['asignacionActiva', 'habilitacionActiva', 'observaciones', 'fotos', 'revisionVigente'])->find($id);
    }

    public function create(array $data): mixed
    {
        return Visita::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $model = $this->obtenerOFallar($id);
        $model->update($data);
        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Visita::destroy($ids);
    }

    public function actualizarEstado(string $id, array $data): mixed
    {
        $model = $this->obtenerOFallar($id);
        $model->update($data);
        return $model->refresh();
    }

    public function reasignarTecnico(string $id, string $nuevoUserId): mixed
    {
        $model = $this->obtenerOFallar($id);
        $model->update(['user_id' => $nuevoUserId]);
        return $model->refresh();
    }

    public function actualizarEstadoRevision(string $id, string $estadoRevision): mixed
    {
        $model = $this->obtenerOFallar($id);
        $model->update(['estado_revision' => $estadoRevision]);
        return $model->refresh();
    }

    public function existeAsignacionActiva(string $visitaId): bool
    {
        return \App\Infrastructure\Visitas\Models\AsignacionVisita::where('visita_id', $visitaId)
            ->where('estado', true)
            ->exists();
    }

    private function obtenerOFallar(string $id): Visita
    {
        $model = Visita::find($id);
        if (! $model) {
            throw new VisitaNotFoundException($id);
        }
        return $model;
    }
}
