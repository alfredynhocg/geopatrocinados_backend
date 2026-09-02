<?php

namespace App\Domain\Patrocinados\Exceptions;

class PatrocinadoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Patrocinado '{$id}' no encontrado.", 404);
    }
}
