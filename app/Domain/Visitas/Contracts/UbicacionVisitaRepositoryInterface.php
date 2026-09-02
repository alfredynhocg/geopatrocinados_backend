<?php

namespace App\Domain\Visitas\Contracts;

interface UbicacionVisitaRepositoryInterface
{
    public function create(array $data): mixed;
    public function listarPorVisita(string $visitaId): array;
}
