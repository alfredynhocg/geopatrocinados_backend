<?php

namespace App\Domain\Dispositivos\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface DispositivoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $userId, ?string $estado): array;

    public function findById(string $id): mixed;

    /** Lanza DispositivoYaRegistradoException si identificador_dispositivo ya existe. */
    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function aprobar(string $id): mixed;

    public function revocar(string $id, string $revokedBy): mixed;
}
