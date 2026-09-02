<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class CreateUsuarioCommand
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
        public string $nombres,
        public string $apellidos,
        public ?string $telefono,
        public string $estado,
    ) {}
}
