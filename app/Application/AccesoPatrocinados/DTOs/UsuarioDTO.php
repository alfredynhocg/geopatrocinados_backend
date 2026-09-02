<?php

namespace App\Application\AccesoPatrocinados\DTOs;

/** Nunca declara password_hash. */
final readonly class UsuarioDTO
{
    public function __construct(
        public string $id,
        public string $username,
        public string $email,
        public string $nombres,
        public string $apellidos,
        public ?string $telefono,
        public string $estado,
        public ?string $ultimo_login_at,
        public array $roles,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            username: $model->username,
            email: $model->email,
            nombres: $model->nombres,
            apellidos: $model->apellidos,
            telefono: $model->telefono,
            estado: $model->estado,
            ultimo_login_at: $model->ultimo_login_at?->toIso8601String(),
            roles: $model->relationLoaded('roles')
                ? $model->roles->pluck('nombre')->all()
                : [],
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
