<?php

namespace App\Infrastructure\Dispositivos\Repositories;

use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Dispositivos\Exceptions\DispositivoNotFoundException;
use App\Domain\Dispositivos\Exceptions\DispositivoYaRegistradoException;
use App\Infrastructure\Dispositivos\Models\Dispositivo;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Database\QueryException;

class EloquentDispositivoRepository implements DispositivoRepositoryInterface
{
    private const SQLSTATE_UNIQUE_VIOLATION = '23505';

    public function paginate(PaginationDTO $pagination, ?string $userId, ?string $estado): array
    {
        $q = Dispositivo::query();

        if ($userId) {
            $q->where('user_id', $userId);
        }
        if ($estado) {
            $q->where('estado', $estado);
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'fecha_registro', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }

    public function findById(string $id): mixed
    {
        $dispositivo = Dispositivo::find($id);

        if (! $dispositivo) {
            throw new DispositivoNotFoundException($id);
        }

        return $dispositivo;
    }

    public function create(array $data): mixed
    {
        try {
            return Dispositivo::create($data);
        } catch (QueryException $e) {
            if ($e->getCode() === self::SQLSTATE_UNIQUE_VIOLATION) {
                throw new DispositivoYaRegistradoException($data['identificador_dispositivo']);
            }

            throw $e;
        }
    }

    public function update(string $id, array $data): mixed
    {
        $dispositivo = $this->findById($id);
        $dispositivo->update($data);

        return $dispositivo->fresh();
    }

    public function aprobar(string $id): mixed
    {
        $dispositivo = $this->findById($id);
        $dispositivo->update(['estado' => 'ACTIVO']);

        return $dispositivo->fresh();
    }

    public function revocar(string $id, string $revokedBy): mixed
    {
        $dispositivo = $this->findById($id);
        $dispositivo->update([
            'estado'           => 'REVOCADO',
            'fecha_revocacion' => now(),
            'revoked_by'       => $revokedBy,
        ]);

        return $dispositivo->fresh();
    }
}
