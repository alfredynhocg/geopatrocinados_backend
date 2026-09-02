<?php

namespace App\Application\Patrocinados\Queries;

final readonly class GetHistorialUbicacionesQuery
{
    public function __construct(public string $patrocinado_id) {}
}
