<?php

namespace App\Application\AccesoPatrocinados\Handlers;

use App\Application\AccesoPatrocinados\Commands\AutenticarUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Domain\AccesoPatrocinados\Contracts\UsuarioRepositoryInterface;
use App\Domain\AccesoPatrocinados\Exceptions\CredencialesInvalidasException;
use App\Domain\AccesoPatrocinados\Exceptions\CuentaBloqueadaException;
use Illuminate\Support\Facades\Hash;

class AutenticarUsuarioHandler
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 30;

    public function __construct(private readonly UsuarioRepositoryInterface $repository) {}

    /**
     * @return array{token: string, usuario: UsuarioDTO}
     */
    public function handle(AutenticarUsuarioCommand $command): array
    {
        $usuario = $this->repository->findByUsernameOrEmail($command->login);

        if ($usuario === null) {
            throw new CredencialesInvalidasException();
        }

        if ($usuario->bloqueado_hasta !== null && $usuario->bloqueado_hasta->isFuture()) {
            throw new CuentaBloqueadaException($usuario->bloqueado_hasta);
        }

        if (! Hash::check($command->password, $usuario->password_hash)) {
            $intentos = $usuario->intentos_fallidos + 1;

            $this->repository->update($usuario->id, [
                'intentos_fallidos' => $intentos,
                'bloqueado_hasta'   => $intentos >= self::MAX_INTENTOS
                    ? now()->addMinutes(self::MINUTOS_BLOQUEO)
                    : null,
            ]);

            throw new CredencialesInvalidasException();
        }

        $this->repository->update($usuario->id, [
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
            'ultimo_login_at'   => now(),
        ]);

        $usuario = $this->repository->findById($usuario->id);
        $token = $usuario->createToken('api')->plainTextToken;

        return [
            'token'   => $token,
            'usuario' => UsuarioDTO::fromModel($usuario->load('roles')),
        ];
    }
}
