<?php

namespace App\Application\Patrocinados\Commands;

final readonly class UpdateTutorCommand
{
    public function __construct(
        public string $id,
        public string $nombres,
        public string $apellidos,
        public string $tipo_parentesco_id,
        public ?string $telefono,
        public string $direccion,
        public bool $estado,
    ) {}
}
