<?php

namespace App\Infrastructure\Sincronizacion\Repositories;

use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;
use App\Domain\Sincronizacion\Exceptions\LoteSincronizacionNotFoundException;
use App\Infrastructure\Sincronizacion\Models\LoteSincronizacion;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentLoteSincronizacionRepository implements LoteSincronizacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $dispositivoId, ?string $estado): array
    {
        $q = LoteSincronizacion::query();

        if ($dispositivoId) {
            $q->where('dispositivo_id', $dispositivoId);
        }
        if ($estado) {
            $q->where('estado', $estado);
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'fecha_inicio', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }

    public function findById(string $id): mixed
    {
        $lote = LoteSincronizacion::find($id);

        if (! $lote) {
            throw new LoteSincronizacionNotFoundException($id);
        }

        return $lote;
    }

    public function create(array $data): mixed
    {
        return LoteSincronizacion::create($data);
    }

    public function cerrar(string $id, int $registrosEnviados, int $registrosRecibidos, string $estado): mixed
    {
        $lote = $this->findById($id);

        $lote->update([
            'fecha_fin'            => now(),
            'registros_enviados'   => $registrosEnviados,
            'registros_recibidos'  => $registrosRecibidos,
            'estado'               => $estado,
        ]);

        return $lote->fresh();
    }
}
