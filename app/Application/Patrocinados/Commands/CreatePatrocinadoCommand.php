<?php

namespace App\Application\Patrocinados\Commands;

final readonly class CreatePatrocinadoCommand
{
    public function __construct(
        public string $codigo,
        public string $nombres,
        public ?string $apellidos,
        public ?string $fecha_nacimiento,
        public ?string $sexo,
        public string $comunidad_id,
        public ?string $ubicacion_id,
        public ?string $unidad_educativa,
        public ?string $nivel_educativo,
        public string $estado_id,
    ) {}
}
