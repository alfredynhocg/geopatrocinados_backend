<?php

namespace App\Domain\Visitas\Contracts;

interface FotoVisitaRepositoryInterface
{
    public function create(array $data): mixed;
    public function findById(string $id): mixed;
    public function listarPorVisita(string $visitaId): array;
}
