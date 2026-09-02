<?php

namespace App\Domain\Dispositivos\Exceptions;

class DispositivoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Dispositivo '{$id}' no encontrado.", 404);
    }
}
