<?php

namespace App\Domain\Sincronizacion\Exceptions;

class LoteSincronizacionNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Lote de sincronización '{$id}' no encontrado.", 404);
    }
}
