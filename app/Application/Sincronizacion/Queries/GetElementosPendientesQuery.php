<?php

namespace App\Application\Sincronizacion\Queries;

final readonly class GetElementosPendientesQuery
{
    public function __construct(public string $lote_id) {}
}
