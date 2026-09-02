<?php

namespace App\Domain\Visitas\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface VisitaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, array $filtros = []): array;
    public function findById(string $id): mixed;
    public function create(array $data): mixed;
    public function update(string $id, array $data): mixed;
    public function delete(string|array $ids): bool;

    /** Actualiza estado + campos propios de iniciar/finalizar/reprogramar. Usado por los Handlers de ciclo de vida. */
    public function actualizarEstado(string $id, array $data): mixed;

    /** Usado únicamente por ReasignarVisitaHandler (Etapa 6b), ver EloquentVisitaRepository::reasignarTecnico(). */
    public function reasignarTecnico(string $id, string $nuevoUserId): mixed;

    /** Usado únicamente por RevisarVisitaHandler (Etapa 6d). */
    public function actualizarEstadoRevision(string $id, string $estadoRevision): mixed;

    public function existeAsignacionActiva(string $visitaId): bool;
}
