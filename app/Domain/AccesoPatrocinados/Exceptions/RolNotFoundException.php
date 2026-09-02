<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class RolNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Rol '{$id}' no encontrado.", 404);
    }
}
