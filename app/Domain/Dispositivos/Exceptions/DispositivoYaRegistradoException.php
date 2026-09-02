<?php

namespace App\Domain\Dispositivos\Exceptions;

class DispositivoYaRegistradoException extends \RuntimeException
{
    public function __construct(string $identificadorDispositivo)
    {
        parent::__construct("El dispositivo '{$identificadorDispositivo}' ya está registrado.", 422);
    }
}
