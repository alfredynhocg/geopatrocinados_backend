<?php

namespace App\Application\Auditoria\DTOs;

final readonly class RegistroAuditoriaDTO
{
    public function __construct(
        public int $id,
        public ?string $user_id,
        public ?string $dispositivo_id,
        public string $accion,
        public string $modulo,
        public ?string $tipo_entidad,
        public ?string $entidad_id,
        public ?array $valores_anteriores,
        public ?array $valores_nuevos,
        public ?string $direccion_ip,
        public ?string $user_agent,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            user_id: $model->user_id,
            dispositivo_id: $model->dispositivo_id,
            accion: $model->accion,
            modulo: $model->modulo,
            tipo_entidad: $model->tipo_entidad,
            entidad_id: $model->entidad_id,
            valores_anteriores: $model->valores_anteriores,
            valores_nuevos: $model->valores_nuevos,
            direccion_ip: $model->direccion_ip,
            user_agent: $model->user_agent,
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
