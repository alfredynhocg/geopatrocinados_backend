<?php

namespace App\Domain\Servicios\Exceptions;

use RuntimeException;

class ServicioNotFoundException extends RuntimeException
{
    public function __construct(int|string $id)
    {
        parent::__construct("Servicio '{$id}' no encontrado.", 404);
    }
}
