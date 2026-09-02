<?php

namespace App\Domain\Geografia\Exceptions;

class DepartamentoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Departamento '{$id}' no encontrado.", 404);
    }
}
