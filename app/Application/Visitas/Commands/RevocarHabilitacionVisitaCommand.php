<?php

namespace App\Application\Visitas\Commands;

final readonly class RevocarHabilitacionVisitaCommand
{
    public function __construct(
        public string $habilitacionId,
        public string $revokedBy,
    ) {}
}
