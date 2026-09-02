<?php

namespace App\Domain\AccesoPatrocinados\Exceptions;

class PermisoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Permiso '{$id}' no encontrado.", 404);
    }
}
