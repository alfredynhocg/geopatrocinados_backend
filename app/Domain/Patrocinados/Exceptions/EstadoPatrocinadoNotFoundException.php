<?php

namespace App\Domain\Patrocinados\Exceptions;

class EstadoPatrocinadoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Estado de patrocinado '{$id}' no encontrado.", 404);
    }
}
