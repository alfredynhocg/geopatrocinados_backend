<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class DeletePermisoCommand
{
    public function __construct(public string $id) {}
}
