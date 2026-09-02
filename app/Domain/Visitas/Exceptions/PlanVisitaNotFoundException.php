<?php

namespace App\Domain\Visitas\Exceptions;

class PlanVisitaNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Plan de visitas '{$id}' no encontrado.", 404);
    }
}
