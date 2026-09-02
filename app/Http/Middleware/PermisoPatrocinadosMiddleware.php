<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Equivalente propio del middleware `permiso:` legado de mentabit, resuelto
 * contra usuarios_roles/roles_permisos/permisos de este módulo — no reutiliza
 * el middleware legado ni sus tablas t_grupopermiso/t_permiso.
 */
class PermisoPatrocinadosMiddleware
{
    /** Uso: ->middleware('permiso-patrocinados:visitas.crear') o 'permiso-patrocinados:usuarios.ver|crear' */
    public function handle(Request $request, Closure $next, string $permisos): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            abort(401, 'No autenticado.');
        }

        foreach (explode('|', $permisos) as $permiso) {
            if ($usuario->tienePermiso($permiso)) {
                return $next($request);
            }
        }

        abort(403, 'No tiene permiso para esta acción.');
    }
}
