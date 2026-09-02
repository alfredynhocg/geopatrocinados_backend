<?php

namespace App\Application\AccesoPatrocinados\Commands;

final readonly class AutenticarUsuarioCommand
{
    public function __construct(
        public string $login,
        public string $password,
    ) {}
}
