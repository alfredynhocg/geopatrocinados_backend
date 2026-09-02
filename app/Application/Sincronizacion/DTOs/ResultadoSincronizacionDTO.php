<?php

namespace App\Application\Sincronizacion\DTOs;

/** Resumen devuelto al cerrar un lote — la app decide reintentos en base a esto. */
final readonly class ResultadoSincronizacionDTO
{
    public function __construct(
        public string $lote_id,
        public string $estado,
        public int $registros_enviados,
        public int $registros_recibidos,
        public int $elementos_sincronizados,
        public int $elementos_con_error,
    ) {}
}
