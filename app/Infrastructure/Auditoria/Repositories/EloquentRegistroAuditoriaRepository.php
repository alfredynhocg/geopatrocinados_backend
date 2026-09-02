<?php

namespace App\Infrastructure\Auditoria\Repositories;

use App\Domain\Auditoria\Contracts\RegistroAuditoriaRepositoryInterface;
use App\Infrastructure\Auditoria\Models\RegistroAuditoria;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentRegistroAuditoriaRepository implements RegistroAuditoriaRepositoryInterface
{
    public function create(array $data): mixed
    {
        return RegistroAuditoria::create($data);
    }

    public function paginate(
        PaginationDTO $pagination,
        ?string $tipoEntidad,
        ?string $entidadId,
        ?string $userId,
        ?string $desde,
        ?string $hasta,
    ): array {
        $q = RegistroAuditoria::query();

        if ($tipoEntidad) {
            $q->where('tipo_entidad', $tipoEntidad);
        }
        if ($entidadId) {
            $q->where('entidad_id', $entidadId);
        }
        if ($userId) {
            $q->where('user_id', $userId);
        }
        if ($desde) {
            $q->where('created_at', '>=', $desde);
        }
        if ($hasta) {
            $q->where('created_at', '<=', $hasta);
        }

        $paginated = $q->orderByDesc('created_at')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return ['data' => $paginated->items(), 'total' => $paginated->total()];
    }
}
