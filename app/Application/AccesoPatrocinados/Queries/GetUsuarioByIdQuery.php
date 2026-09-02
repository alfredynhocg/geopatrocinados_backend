<?php

namespace App\Application\AccesoPatrocinados\Queries;

final readonly class GetUsuarioByIdQuery
{
    public function __construct(public string $id) {}
}
