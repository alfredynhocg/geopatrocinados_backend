<?php

namespace App\Application\Visitas\DTOs;

/** Nunca expone clave_almacenamiento cruda — solo urlFirmada, generada por el Controller/Service. */
final readonly class FotoVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public ?string $nombreArchivo,
        public string $tipoArchivo,
        public int $tamanio,
        public string $hashSha256,
        public ?string $fechaCaptura,
        public bool $cifrada,
        public ?string $urlFirmada = null,
    ) {}

    public static function fromModel(object $model, ?string $urlFirmada = null): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            nombreArchivo: $model->nombre_archivo,
            tipoArchivo: $model->tipo_archivo,
            tamanio: (int) $model->tamanio,
            hashSha256: $model->hash_sha256,
            fechaCaptura: $model->fecha_captura?->toIso8601String(),
            cifrada: (bool) $model->cifrada,
            urlFirmada: $urlFirmada,
        );
    }
}
