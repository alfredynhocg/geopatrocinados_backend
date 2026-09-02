<?php

namespace App\Application\Geografia\Commands;

final readonly class DeleteMunicipioCommand
{
    public function __construct(public string $id) {}
}
