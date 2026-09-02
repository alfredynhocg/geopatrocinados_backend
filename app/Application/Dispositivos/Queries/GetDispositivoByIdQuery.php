<?php

namespace App\Application\Dispositivos\Queries;

final readonly class GetDispositivoByIdQuery
{
    public function __construct(public string $id) {}
}
