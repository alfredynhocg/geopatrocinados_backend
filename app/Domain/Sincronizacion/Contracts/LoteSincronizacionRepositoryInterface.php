<?php

namespace App\Domain\Sincronizacion\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface LoteSincronizacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $dispositivoId, ?string $estado): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function cerrar(string $id, int $registrosEnviados, int $registrosRecibidos, string $estado): mixed;
}
