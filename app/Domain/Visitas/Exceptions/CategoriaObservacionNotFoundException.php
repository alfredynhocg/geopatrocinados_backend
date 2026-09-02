<?php

namespace App\Domain\Visitas\Exceptions;

class CategoriaObservacionNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Categoría de observación '{$id}' no encontrada.", 404);
    }
}
