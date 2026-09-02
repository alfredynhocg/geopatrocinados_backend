<?php

namespace App\Application\Dispositivos\Commands;

final readonly class RevocarDispositivoCommand
{
    public function __construct(
        public string $id,
        public string $revoked_by,
    ) {}
}
