<?php

namespace App\Domain\Impresion\Exceptions;

class ImpresoraNoDisponibleException extends \RuntimeException
{
    public function __construct(string $detalle)
    {
        parent::__construct("No se pudo conectar con la impresora térmica: {$detalle}", 503);
    }
}
