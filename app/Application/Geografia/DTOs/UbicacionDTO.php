<?php

namespace App\Application\Geografia\DTOs;

/** latitude/longitude como floats planos — nunca expone el WKB/hex crudo de punto_geografico. */
final readonly class UbicacionDTO
{
    public function __construct(
        public string $id,
        public string $comunidad_id,
        public string $nombre,
        public ?string $tipo,
        public ?string $direccion,
        public ?float $latitude,
        public ?float $longitude,
        public ?float $precision_metros,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            comunidad_id: $model->comunidad_id,
            nombre: $model->nombre,
            tipo: $model->tipo,
            direccion: $model->direccion,
            latitude: $model->latitude !== null ? (float) $model->latitude : null,
            longitude: $model->longitude !== null ? (float) $model->longitude : null,
            precision_metros: $model->precision_metros !== null ? (float) $model->precision_metros : null,
            estado: (bool) $model->estado,
        );
    }
}
