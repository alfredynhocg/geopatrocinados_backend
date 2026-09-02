<?php

namespace App\Application\Dispositivos\Commands;

/** Deliberadamente sin `estado`: el ciclo de vida se cambia vía Aprobar/Revocar. */
final readonly class UpdateDispositivoCommand
{
    public function __construct(
        public string $id,
        public ?string $nombre_dispositivo,
        public ?string $version_sistema,
        public ?string $version_aplicacion,
    ) {}
}
