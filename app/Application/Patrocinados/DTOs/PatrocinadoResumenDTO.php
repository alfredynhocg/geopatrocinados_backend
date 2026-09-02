<?php

namespace App\Application\Patrocinados\DTOs;

/**
 * Variante sin datos sensibles del menor: sin tutores, sin dirección,
 * edad aproximada en vez de fecha de nacimiento exacta. Es el DTO por
 * defecto de cualquier listado — ver GetPatrocinadosQueryHandler.
 */
final readonly class PatrocinadoResumenDTO
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $nombres,
        public ?string $apellidos,
        public ?int $edad_aproximada,
        public string $comunidad_id,
        public ?string $nivel_educativo,
        public string $estado_id,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            codigo: $model->codigo,
            nombres: $model->nombres,
            apellidos: $model->apellidos,
            edad_aproximada: $model->fecha_nacimiento?->age,
            comunidad_id: $model->comunidad_id,
            nivel_educativo: $model->nivel_educativo,
            estado_id: $model->estado_id,
        );
    }
}
