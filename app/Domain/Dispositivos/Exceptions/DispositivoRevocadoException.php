<?php

namespace App\Domain\Dispositivos\Exceptions;

class DispositivoRevocadoException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("El dispositivo '{$id}' está revocado y no puede usarse.", 403);
    }
}
