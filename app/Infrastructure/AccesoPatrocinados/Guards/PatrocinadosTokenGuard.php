<?php

namespace App\Infrastructure\AccesoPatrocinados\Guards;

use App\Infrastructure\AccesoPatrocinados\Models\PersonalAccessToken;
use Illuminate\Http\Request;

/**
 * Guard de token propio del módulo, aislado del guard `sanctum` que usa
 * mentabit (App\Models\User, conexión mysql). Reutiliza el formato de token
 * de Sanctum ("{id}|{plaintext}", hash sha256) pero resuelve siempre contra
 * personal_access_tokens en pgsql_patrocinados — ver PersonalAccessToken.
 *
 * Registrado como driver 'patrocinados' en el guard homónimo de config/auth.php.
 */
class PatrocinadosTokenGuard
{
    public function __invoke(Request $request)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken
            || ($accessToken->expires_at !== null && $accessToken->expires_at->isPast())
            || ! $accessToken->tokenable
        ) {
            return null;
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();

        $tokenable = $accessToken->tokenable;
        $tokenable->withAccessToken($accessToken);

        return $tokenable;
    }
}
