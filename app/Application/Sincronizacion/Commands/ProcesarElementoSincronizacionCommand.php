<?php

namespace App\Application\Sincronizacion\Commands;

final readonly class ProcesarElementoSincronizacionCommand
{
    public function __construct(
        public string $lote_id,
        public string $tipo_entidad,
        public string $entidad_id,
        public string $operacion,
        public ?string $hash_datos,
        public array $payload,
    ) {}
}
