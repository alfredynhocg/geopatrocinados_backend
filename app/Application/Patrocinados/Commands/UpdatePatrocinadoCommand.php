<?php

namespace App\Application\Patrocinados\Commands;

/**
 * Deliberadamente SIN comunidad_id/ubicacion_id: el único camino válido
 * para mover a un patrocinado es CambiarUbicacionPatrocinadoCommand.
 */
final readonly class UpdatePatrocinadoCommand
{
    public function __construct(
        public string $id,
        public string $nombres,
        public ?string $apellidos,
        public ?string $fecha_nacimiento,
        public ?string $sexo,
        public ?string $unidad_educativa,
        public ?string $nivel_educativo,
        public string $estado_id,
    ) {}
}
