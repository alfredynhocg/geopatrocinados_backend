<?php

namespace App\Domain\Visitas\Contracts;

interface HabilitacionVisitaRepositoryInterface
{
    public function findById(string $id): mixed;
    public function findActiva(string $visitaId, string $dispositivoId): mixed;
    public function create(array $data): mixed;
    public function revocar(string $id, string $revokedBy): mixed;
}
