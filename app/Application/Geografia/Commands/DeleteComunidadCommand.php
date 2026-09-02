<?php

namespace App\Application\Geografia\Commands;

final readonly class DeleteComunidadCommand
{
    public function __construct(public string $id) {}
}
