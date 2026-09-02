<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\AccesoPatrocinados\Commands\AutenticarUsuarioCommand;
use App\Application\AccesoPatrocinados\DTOs\UsuarioDTO;
use App\Application\AccesoPatrocinados\Handlers\AutenticarUsuarioHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AutenticarUsuarioHandler $autenticarHandler) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $resultado = $this->autenticarHandler->handle(new AutenticarUsuarioCommand(
            login: $request->login,
            password: $request->password,
        ));

        return response()->json($resultado);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(UsuarioDTO::fromModel($request->user()->load('roles')));
    }
}
