<?php

namespace App\Domain\Patrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface PatrocinadoRepositoryInterface
{
    public function paginate(
        PaginationDTO $pagination,
        ?string $comunidadId,
        ?string $estadoId,
        ?string $nivelEducativo,
    ): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;

    /**
     * Único método autorizado para escribir comunidad_id/ubicacion_id.
     * Usado exclusivamente por CambiarUbicacionPatrocinadoHandler.
     */
    public function moverUbicacion(string $patrocinadoId, string $comunidadId, ?string $ubicacionId): mixed;
}
