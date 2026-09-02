<?php

namespace App\Application\Dispositivos\Commands;

final readonly class AprobarDispositivoCommand
{
    public function __construct(public string $id) {}
}
