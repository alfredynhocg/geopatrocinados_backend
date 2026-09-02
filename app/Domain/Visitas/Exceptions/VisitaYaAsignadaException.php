<?php

namespace App\Domain\Visitas\Exceptions;

class VisitaYaAsignadaException extends \RuntimeException
{
    public function __construct(string $visitaId)
    {
        parent::__construct("La visita '{$visitaId}' ya tiene una asignación activa.", 422);
    }
}
