<?php

use App\Http\Controllers\Api\Patrocinados\AuthController;
use App\Http\Controllers\Api\Patrocinados\CategoriaObservacionController;
use App\Http\Controllers\Api\Patrocinados\ComunidadController;
use App\Http\Controllers\Api\Patrocinados\DepartamentoController;
use App\Http\Controllers\Api\Patrocinados\DispositivoController;
use App\Http\Controllers\Api\Patrocinados\EstadoPatrocinadoController;
use App\Http\Controllers\Api\Patrocinados\FotoVisitaController;
use App\Http\Controllers\Api\Patrocinados\HabilitacionVisitaController;
use App\Http\Controllers\Api\Patrocinados\MotivoVisitaController;
use App\Http\Controllers\Api\Patrocinados\MunicipioController;
use App\Http\Controllers\Api\Patrocinados\ObservacionVisitaController;
use App\Http\Controllers\Api\Patrocinados\PatrocinadoController;
use App\Http\Controllers\Api\Patrocinados\PermisoController;
use App\Http\Controllers\Api\Patrocinados\PingController;
use App\Http\Controllers\Api\Patrocinados\PlanVisitaController;
use App\Http\Controllers\Api\Patrocinados\RegistroAuditoriaController;
use App\Http\Controllers\Api\Patrocinados\RevisionVisitaController;
use App\Http\Controllers\Api\Patrocinados\RolController;
use App\Http\Controllers\Api\Patrocinados\SincronizacionController;
use App\Http\Controllers\Api\Patrocinados\TipoParentescoController;
use App\Http\Controllers\Api\Patrocinados\TutorController;
use App\Http\Controllers\Api\Patrocinados\UbicacionController;
use App\Http\Controllers\Api\Patrocinados\UbicacionVisitaController;
use App\Http\Controllers\Api\Patrocinados\UsuarioController;
use App\Http\Controllers\Api\Patrocinados\VisitaController;
use Illuminate\Support\Facades\Route;

Route::prefix('patrocinados')->group(function () {
    Route::get('/ping', PingController::class);

    // Etapa 2 — AccesoPatrocinados -------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::middleware('auth:patrocinados')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:patrocinados')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->middleware('permiso-patrocinados:usuarios.ver');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->middleware('permiso-patrocinados:usuarios.crear');
        Route::get('/usuarios/{id}', [UsuarioController::class, 'show'])->middleware('permiso-patrocinados:usuarios.ver');
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->middleware('permiso-patrocinados:usuarios.editar');
        Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->middleware('permiso-patrocinados:usuarios.eliminar');
        Route::post('/usuarios/{id}/roles', [UsuarioController::class, 'asignarRol'])->middleware('permiso-patrocinados:usuarios.editar');
        Route::delete('/usuarios/{id}/roles/{rolId}', [UsuarioController::class, 'revocarRol'])->middleware('permiso-patrocinados:usuarios.editar');

        Route::get('/roles', [RolController::class, 'index'])->middleware('permiso-patrocinados:roles.ver');
        Route::post('/roles', [RolController::class, 'store'])->middleware('permiso-patrocinados:roles.crear');
        Route::put('/roles/{id}', [RolController::class, 'update'])->middleware('permiso-patrocinados:roles.editar');
        Route::delete('/roles/{id}', [RolController::class, 'destroy'])->middleware('permiso-patrocinados:roles.eliminar');
        Route::post('/roles/{id}/permisos', [RolController::class, 'asignarPermiso'])->middleware('permiso-patrocinados:roles.editar');
        Route::delete('/roles/{id}/permisos/{permisoId}', [RolController::class, 'revocarPermiso'])->middleware('permiso-patrocinados:roles.editar');

        Route::get('/permisos', [PermisoController::class, 'index'])->middleware('permiso-patrocinados:permisos.ver');
        Route::post('/permisos', [PermisoController::class, 'store'])->middleware('permiso-patrocinados:permisos.crear');
        Route::put('/permisos/{id}', [PermisoController::class, 'update'])->middleware('permiso-patrocinados:permisos.editar');
        Route::delete('/permisos/{id}', [PermisoController::class, 'destroy'])->middleware('permiso-patrocinados:permisos.eliminar');

        // Etapa 3 — Geografia --------------------------------------------------
        Route::get('/departamentos', [DepartamentoController::class, 'index'])->middleware('permiso-patrocinados:geografia.ver');
        Route::post('/departamentos', [DepartamentoController::class, 'store'])->middleware('permiso-patrocinados:geografia.crear');
        Route::put('/departamentos/{id}', [DepartamentoController::class, 'update'])->middleware('permiso-patrocinados:geografia.editar');
        Route::delete('/departamentos/{id}', [DepartamentoController::class, 'destroy'])->middleware('permiso-patrocinados:geografia.eliminar');

        Route::get('/municipios', [MunicipioController::class, 'index'])->middleware('permiso-patrocinados:geografia.ver');
        Route::post('/municipios', [MunicipioController::class, 'store'])->middleware('permiso-patrocinados:geografia.crear');
        Route::put('/municipios/{id}', [MunicipioController::class, 'update'])->middleware('permiso-patrocinados:geografia.editar');
        Route::delete('/municipios/{id}', [MunicipioController::class, 'destroy'])->middleware('permiso-patrocinados:geografia.eliminar');

        Route::get('/comunidades', [ComunidadController::class, 'index'])->middleware('permiso-patrocinados:geografia.ver');
        Route::post('/comunidades', [ComunidadController::class, 'store'])->middleware('permiso-patrocinados:geografia.crear');
        Route::put('/comunidades/{id}', [ComunidadController::class, 'update'])->middleware('permiso-patrocinados:geografia.editar');
        Route::delete('/comunidades/{id}', [ComunidadController::class, 'destroy'])->middleware('permiso-patrocinados:geografia.eliminar');

        Route::get('/ubicaciones', [UbicacionController::class, 'index'])->middleware('permiso-patrocinados:geografia.ver');
        Route::post('/ubicaciones', [UbicacionController::class, 'store'])->middleware('permiso-patrocinados:geografia.crear');
        Route::put('/ubicaciones/{id}', [UbicacionController::class, 'update'])->middleware('permiso-patrocinados:geografia.editar');
        Route::delete('/ubicaciones/{id}', [UbicacionController::class, 'destroy'])->middleware('permiso-patrocinados:geografia.eliminar');

        // Etapa 4 — Dispositivos -------------------------------------------------
        Route::get('/dispositivos', [DispositivoController::class, 'index'])->middleware('permiso-patrocinados:dispositivos.ver');
        Route::post('/dispositivos', [DispositivoController::class, 'store']);
        Route::get('/dispositivos/{id}', [DispositivoController::class, 'show'])->middleware('permiso-patrocinados:dispositivos.ver');
        Route::put('/dispositivos/{id}', [DispositivoController::class, 'update'])->middleware('permiso-patrocinados:dispositivos.editar');
        Route::post('/dispositivos/{id}/aprobar', [DispositivoController::class, 'aprobar'])->middleware('permiso-patrocinados:dispositivos.aprobar');
        Route::post('/dispositivos/{id}/revocar', [DispositivoController::class, 'revocar'])->middleware('permiso-patrocinados:dispositivos.revocar');

        // Etapa 5 — Patrocinados ---------------------------------------------------
        Route::get('/ninos', [PatrocinadoController::class, 'index'])->middleware('permiso-patrocinados:patrocinados.ver');
        Route::post('/ninos', [PatrocinadoController::class, 'store'])->middleware('permiso-patrocinados:patrocinados.crear');
        Route::get('/ninos/{id}', [PatrocinadoController::class, 'show'])->middleware('permiso-patrocinados:patrocinados.ver');
        Route::put('/ninos/{id}', [PatrocinadoController::class, 'update'])->middleware('permiso-patrocinados:patrocinados.editar');
        Route::delete('/ninos/{id}', [PatrocinadoController::class, 'destroy'])->middleware('permiso-patrocinados:patrocinados.eliminar');
        Route::post('/ninos/{id}/cambiar-ubicacion', [PatrocinadoController::class, 'cambiarUbicacion'])->middleware('permiso-patrocinados:patrocinados.editar');
        Route::get('/ninos/{id}/historial-ubicaciones', [PatrocinadoController::class, 'historialUbicaciones'])->middleware('permiso-patrocinados:patrocinados.ver');
        Route::get('/ninos/{patrocinadoId}/tutores', [TutorController::class, 'index'])->middleware('permiso-patrocinados:patrocinados.ver');
        Route::post('/ninos/{patrocinadoId}/tutores', [TutorController::class, 'store'])->middleware('permiso-patrocinados:patrocinados.editar');
        Route::put('/tutores/{id}', [TutorController::class, 'update'])->middleware('permiso-patrocinados:patrocinados.editar');
        Route::delete('/tutores/{id}', [TutorController::class, 'destroy'])->middleware('permiso-patrocinados:patrocinados.editar');

        Route::get('/estados-patrocinados', [EstadoPatrocinadoController::class, 'index'])->middleware('permiso-patrocinados:patrocinados.ver');
        Route::post('/estados-patrocinados', [EstadoPatrocinadoController::class, 'store'])->middleware('permiso-patrocinados:patrocinados.crear');
        Route::put('/estados-patrocinados/{id}', [EstadoPatrocinadoController::class, 'update'])->middleware('permiso-patrocinados:patrocinados.editar');
        Route::delete('/estados-patrocinados/{id}', [EstadoPatrocinadoController::class, 'destroy'])->middleware('permiso-patrocinados:patrocinados.eliminar');

        Route::get('/tipos-parentescos', [TipoParentescoController::class, 'index'])->middleware('permiso-patrocinados:patrocinados.ver');
        Route::post('/tipos-parentescos', [TipoParentescoController::class, 'store'])->middleware('permiso-patrocinados:patrocinados.crear');
        Route::put('/tipos-parentescos/{id}', [TipoParentescoController::class, 'update'])->middleware('permiso-patrocinados:patrocinados.editar');
        Route::delete('/tipos-parentescos/{id}', [TipoParentescoController::class, 'destroy'])->middleware('permiso-patrocinados:patrocinados.eliminar');

        // Etapa 6 — Visitas ----------------------------------------------------------
        Route::get('/planes-visitas', [PlanVisitaController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
        Route::post('/planes-visitas', [PlanVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.crear');
        Route::put('/planes-visitas/{id}', [PlanVisitaController::class, 'update'])->middleware('permiso-patrocinados:visitas.editar');
        Route::delete('/planes-visitas/{id}', [PlanVisitaController::class, 'destroy'])->middleware('permiso-patrocinados:visitas.eliminar');

        Route::get('/motivos-visitas', [MotivoVisitaController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
        Route::post('/motivos-visitas', [MotivoVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.crear');
        Route::put('/motivos-visitas/{id}', [MotivoVisitaController::class, 'update'])->middleware('permiso-patrocinados:visitas.editar');
        Route::delete('/motivos-visitas/{id}', [MotivoVisitaController::class, 'destroy'])->middleware('permiso-patrocinados:visitas.eliminar');

        Route::get('/categorias-observaciones', [CategoriaObservacionController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
        Route::post('/categorias-observaciones', [CategoriaObservacionController::class, 'store'])->middleware('permiso-patrocinados:visitas.crear');
        Route::put('/categorias-observaciones/{id}', [CategoriaObservacionController::class, 'update'])->middleware('permiso-patrocinados:visitas.editar');
        Route::delete('/categorias-observaciones/{id}', [CategoriaObservacionController::class, 'destroy'])->middleware('permiso-patrocinados:visitas.eliminar');

        Route::get('/visitas', [VisitaController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
        Route::post('/visitas', [VisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.crear');
        Route::get('/visitas/{id}', [VisitaController::class, 'show'])->middleware('permiso-patrocinados:visitas.ver');
        Route::put('/visitas/{id}', [VisitaController::class, 'update'])->middleware('permiso-patrocinados:visitas.editar');
        Route::post('/visitas/{id}/reasignar', [VisitaController::class, 'reasignar'])->middleware('permiso-patrocinados:visitas.editar');
        Route::post('/visitas/{id}/iniciar', [VisitaController::class, 'iniciar'])->middleware('permiso-patrocinados:visitas.editar');
        Route::post('/visitas/{id}/finalizar', [VisitaController::class, 'finalizar'])->middleware('permiso-patrocinados:visitas.editar');
        Route::post('/visitas/{id}/reprogramar', [VisitaController::class, 'reprogramar'])->middleware('permiso-patrocinados:visitas.editar');

        Route::post('/visitas/{visitaId}/habilitaciones', [HabilitacionVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.editar');
        Route::post('/habilitaciones-visitas/{id}/revocar', [HabilitacionVisitaController::class, 'revocar'])->middleware('permiso-patrocinados:visitas.editar');

        Route::post('/visitas/{visitaId}/ubicaciones', [UbicacionVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.editar');

        Route::get('/visitas/{visitaId}/observaciones', [ObservacionVisitaController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
        Route::post('/visitas/{visitaId}/observaciones', [ObservacionVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.editar');

        Route::post('/visitas/{visitaId}/fotos', [FotoVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.editar');
        Route::get('/visitas/{visitaId}/fotos/{fotoId}', [FotoVisitaController::class, 'show'])->middleware('permiso-patrocinados:visitas.ver');

        Route::get('/visitas/{visitaId}/revisiones', [RevisionVisitaController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
        Route::post('/visitas/{visitaId}/revisiones', [RevisionVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.revisar');

        // Etapa 8 — Auditoria ---------------------------------------------------
        Route::get('/registros-auditoria', [RegistroAuditoriaController::class, 'index'])->middleware('permiso-patrocinados:auditoria.ver');
    });

    // Etapa 7 — Sincronizacion (autenticado con el token del dispositivo, no requiere permiso-patrocinados) ---
    Route::middleware('auth:patrocinados')->prefix('sincronizacion')->group(function () {
        Route::post('/lotes', [SincronizacionController::class, 'iniciarLote']);
        Route::post('/lotes/{loteId}/elementos', [SincronizacionController::class, 'procesarElemento']);
        Route::post('/lotes/{loteId}/cerrar', [SincronizacionController::class, 'cerrarLote']);
    });
});
