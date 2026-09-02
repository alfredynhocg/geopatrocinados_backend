<?php

namespace App\Domain\Visitas\Exceptions;

class MotivoVisitaNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Motivo de visita '{$id}' no encontrado.", 404);
    }
}
