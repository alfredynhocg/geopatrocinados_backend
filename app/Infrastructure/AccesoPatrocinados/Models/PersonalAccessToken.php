<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Al crear un token vía Usuario::createToken() (HasApiTokens), Eloquent ya
 * propaga la conexión del padre (pgsql_patrocinados) al insertar la fila —
 * pero PersonalAccessToken::findToken() es un método estático que Sanctum usa
 * para autenticar cada request y NO hereda conexión de ningún padre, así que
 * sin este subclase cae en la conexión default (mysql, la de mentabit).
 *
 * Se usa únicamente desde PatrocinadosTokenGuard — nunca se registra vía
 * Sanctum::usePersonalAccessTokenModel(), que sería global y rompería los
 * tokens de mentabit (App\Models\User, conexión mysql).
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use UsaConexionPatrocinados;
}
