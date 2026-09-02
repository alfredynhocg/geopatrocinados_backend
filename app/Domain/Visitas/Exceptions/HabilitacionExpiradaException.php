<?php

namespace App\Domain\Visitas\Exceptions;

class HabilitacionExpiradaException extends \RuntimeException
{
    public function __construct(string $habilitacionId)
    {
        parent::__construct("La habilitación '{$habilitacionId}' expiró.", 403);
    }
}
