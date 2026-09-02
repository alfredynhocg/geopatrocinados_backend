<?php

namespace App\Domain\Geografia\Exceptions;

class ComunidadNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Comunidad '{$id}' no encontrada.", 404);
    }
}
