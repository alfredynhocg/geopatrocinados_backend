<?php

namespace App\Domain\Geografia\Exceptions;

class UbicacionNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Ubicación '{$id}' no encontrada.", 404);
    }
}
