<?php

namespace App\Domain\Sincronizacion\Exceptions;

/**
 * Se lanza dentro de un adapter cuando la versión del cliente no coincide
 * con la del servidor (last-write-wins). ProcesarElementoSincronizacionHandler
 * la captura SIEMPRE — nunca debe llegar a un Controller como 5xx.
 */
class ConflictoVersionException extends \RuntimeException
{
    public function __construct(string $entidadId)
    {
        parent::__construct("Conflicto de versión al sincronizar la entidad '{$entidadId}'.");
    }
}
