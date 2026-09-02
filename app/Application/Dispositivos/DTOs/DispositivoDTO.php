<?php

namespace App\Application\Dispositivos\DTOs;

final readonly class DispositivoDTO
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $identificador_dispositivo,
        public ?string $nombre_dispositivo,
        public string $plataforma,
        public ?string $version_sistema,
        public ?string $version_aplicacion,
        public string $estado,
        public ?string $ultima_sincronizacion_at,
        public string $fecha_registro,
        public ?string $fecha_revocacion,
        public ?string $revoked_by,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            user_id: $model->user_id,
            identificador_dispositivo: $model->identificador_dispositivo,
            nombre_dispositivo: $model->nombre_dispositivo,
            plataforma: $model->plataforma,
            version_sistema: $model->version_sistema,
            version_aplicacion: $model->version_aplicacion,
            estado: $model->estado,
            ultima_sincronizacion_at: $model->ultima_sincronizacion_at?->toIso8601String(),
            fecha_registro: $model->fecha_registro->toIso8601String(),
            fecha_revocacion: $model->fecha_revocacion?->toIso8601String(),
            revoked_by: $model->revoked_by,
        );
    }
}
