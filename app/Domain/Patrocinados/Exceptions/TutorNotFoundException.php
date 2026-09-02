<?php

namespace App\Domain\Patrocinados\Exceptions;

class TutorNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Tutor '{$id}' no encontrado.", 404);
    }
}
