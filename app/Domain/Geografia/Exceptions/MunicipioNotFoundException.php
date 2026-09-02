<?php

namespace App\Domain\Geografia\Exceptions;

class MunicipioNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Municipio '{$id}' no encontrado.", 404);
    }
}
