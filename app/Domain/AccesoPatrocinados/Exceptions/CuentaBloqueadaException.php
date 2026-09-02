<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class CuentaBloqueadaException extends \RuntimeException
{
    public function __construct(\DateTimeInterface $bloqueadoHasta)
    {
        parent::__construct(
            "Cuenta bloqueada hasta {$bloqueadoHasta->format('Y-m-d H:i:s')} por intentos fallidos.",
            403,
        );
    }
}
