<?php

namespace App\Application\Sincronizacion\DTOs;

final readonly class ElementoSincronizacionDTO
{
    public function __construct(
        public string $id,
        public string $lote_sincronizacion_id,
        public string $tipo_entidad,
        public string $entidad_id,
        public string $operacion,
        public ?string $hash_datos,
        public string $estado,
        public int $intentos,
        public ?string $mensaje_error,
        public ?string $fecha_sincronizacion,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            lote_sincronizacion_id: $model->lote_sincronizacion_id,
            tipo_entidad: $model->tipo_entidad,
            entidad_id: $model->entidad_id,
            operacion: $model->operacion,
            hash_datos: $model->hash_datos,
            estado: $model->estado,
            intentos: $model->intentos,
            mensaje_error: $model->mensaje_error,
            fecha_sincronizacion: $model->fecha_sincronizacion?->toIso8601String(),
        );
    }
}
