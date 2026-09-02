<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class DeleteUsuarioCommand
{
    public function __construct(public string $id) {}
}
