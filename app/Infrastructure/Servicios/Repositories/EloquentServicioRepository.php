<?php

namespace App\Infrastructure\Servicios\Repositories;

use App\Application\Servicios\DTOs\ServicioDTO;
use App\Domain\Servicios\Contracts\ServicioRepositoryInterface;
use App\Domain\Servicios\Exceptions\ServicioNotFoundException;
use App\Infrastructure\Servicios\Models\Servicio;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentServicioRepository implements ServicioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $categoria = null, bool $soloDestacados = false): array
    {
        $q = Servicio::query();

        if ($categoria) {
            $q->where('categoria', $categoria);
        }

        if ($soloDestacados) {
            $q->where('destacado', true);
        }

        if ($pagination->query) {
            $q->where(function ($sq) use ($pagination) {
                $sq->where('titulo', 'like', "%{$pagination->query}%")
                   ->orWhere('descripcion_corta', 'like', "%{$pagination->query}%");
            });
        }

        $paginated = $q->orderBy('orden')
            ->orderByDesc('id')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($m) => ServicioDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(int $id): ServicioDTO
    {
        $model = Servicio::find($id);
        if (! $model) {
            throw new ServicioNotFoundException($id);
        }

        return ServicioDTO::fromModel($model);
    }

    public function findBySlug(string $slug): ServicioDTO
    {
        $model = Servicio::where('slug', $slug)->first();
        if (! $model) {
            throw new ServicioNotFoundException($slug);
        }

        return ServicioDTO::fromModel($model);
    }

    public function create(array $data): ServicioDTO
    {
        $model = Servicio::create($data);

        return ServicioDTO::fromModel($model);
    }

    public function update(int $id, array $data): ServicioDTO
    {
        $model = Servicio::find($id);
        if (! $model) {
            throw new ServicioNotFoundException($id);
        }
        $model->update($data);

        return ServicioDTO::fromModel($model);
    }

    public function delete(int|array $ids): bool
    {
        return Servicio::destroy($ids) > 0;
    }
}
