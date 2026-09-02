<?php

namespace App\Domain\Visitas\Contracts;

interface AsignacionVisitaRepositoryInterface
{
    public function findActivaPorVisita(string $visitaId): mixed;
    public function cerrarActiva(string $visitaId): void;
    public function create(array $data): mixed;
}
