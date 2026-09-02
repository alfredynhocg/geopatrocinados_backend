<?php

namespace App\Domain\Visitas\Contracts;

interface ObservacionVisitaRepositoryInterface
{
    public function create(array $data): mixed;
    public function listarPorVisita(string $visitaId): array;
}
