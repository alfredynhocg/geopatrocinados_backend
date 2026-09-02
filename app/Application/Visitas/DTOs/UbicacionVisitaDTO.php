<?php

namespace App\Application\Visitas\DTOs;

final readonly class UbicacionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public string $dispositivoId,
        public float $latitude,
        public float $longitude,
        public ?float $precisionMetros,
        public string $fuente,
        public string $fechaCaptura,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            dispositivoId: $model->dispositivo_id,
            latitude: (float) $model->latitude,
            longitude: (float) $model->longitude,
            precisionMetros: $model->precision_metros !== null ? (float) $model->precision_metros : null,
            fuente: $model->fuente,
            fechaCaptura: $model->fecha_captura?->toIso8601String(),
        );
    }
}
