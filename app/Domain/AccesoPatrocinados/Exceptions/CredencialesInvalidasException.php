<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class CredencialesInvalidasException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Credenciales inválidas.', 401);
    }
}
