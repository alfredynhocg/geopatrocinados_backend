<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class UsuarioNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Usuario '{$id}' no encontrado.", 404);
    }
}
