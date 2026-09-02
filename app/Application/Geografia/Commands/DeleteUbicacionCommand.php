<?php

namespace App\Application\Geografia\Commands;

final readonly class DeleteUbicacionCommand
{
    public function __construct(public string $id) {}
}
