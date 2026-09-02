<?php

namespace App\Domain\Patrocinados\Contracts;

interface HistorialUbicacionRepositoryInterface
{
    /** Timeline completo de un patrocinado, más reciente primero. */
    public function listByPatrocinado(string $patrocinadoId): array;

    /** La fila vigente (fecha_fin IS NULL), o null si nunca tuvo ubicación asignada. */
    public function findAbiertoByPatrocinado(string $patrocinadoId): mixed;

    /** Cierra la fila vigente poniendo fecha_fin = hoy. */
    public function cerrar(string $id): void;

    public function create(array $data): mixed;
}
