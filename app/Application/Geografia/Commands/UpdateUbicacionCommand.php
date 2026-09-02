<?php

namespace App\Application\Geografia\Commands;

final readonly class UpdateUbicacionCommand
{
    public function __construct(
        public string $id,
        public string $comunidad_id,
        public string $nombre,
        public ?string $tipo,
        public ?string $direccion,
        public float $latitude,
        public float $longitude,
        public ?float $precision_metros,
        public bool $estado,
    ) {}
}
