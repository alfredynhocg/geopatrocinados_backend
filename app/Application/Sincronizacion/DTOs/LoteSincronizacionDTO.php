<?php

namespace App\Application\Sincronizacion\DTOs;

final readonly class LoteSincronizacionDTO
{
    public function __construct(
        public string $id,
        public string $dispositivo_id,
        public string $user_id,
        public string $fecha_inicio,
        public ?string $fecha_fin,
        public int $registros_enviados,
        public int $registros_recibidos,
        public string $estado,
        public ?string $mensaje_error,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            dispositivo_id: $model->dispositivo_id,
            user_id: $model->user_id,
            fecha_inicio: $model->fecha_inicio->toIso8601String(),
            fecha_fin: $model->fecha_fin?->toIso8601String(),
            registros_enviados: $model->registros_enviados,
            registros_recibidos: $model->registros_recibidos,
            estado: $model->estado,
            mensaje_error: $model->mensaje_error,
        );
    }
}
