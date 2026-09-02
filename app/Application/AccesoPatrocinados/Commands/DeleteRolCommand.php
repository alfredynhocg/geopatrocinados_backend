<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class DeleteRolCommand
{
    public function __construct(public string $id) {}
}
