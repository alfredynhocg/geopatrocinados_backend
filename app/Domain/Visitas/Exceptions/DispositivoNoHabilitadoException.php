<?php

namespace App\Domain\Visitas\Exceptions;

class DispositivoNoHabilitadoException extends \RuntimeException
{
    public function __construct(string $visitaId, string $dispositivoId)
    {
        parent::__construct(
            "El dispositivo '{$dispositivoId}' no tiene una habilitación activa para la visita '{$visitaId}'.",
            403
        );
    }
}
