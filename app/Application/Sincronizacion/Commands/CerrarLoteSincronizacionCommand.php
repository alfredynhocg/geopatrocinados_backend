<?php

namespace App\Application\Sincronizacion\Commands;

final readonly class CerrarLoteSincronizacionCommand
{
    public function __construct(
        public string $lote_id,
        public int $registros_enviados,
        public int $registros_recibidos,
    ) {}
}
