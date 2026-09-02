<?php

use App\Http\Controllers\Api\AjusteController;
use App\Http\Controllers\Api\BannerPortalController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EtiquetaController;
use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\MensajeContactoController;
use App\Http\Controllers\Api\RedSocialController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\WhatsAppAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/redes-sociales', [RedSocialController::class, 'index'])
        ->middleware('permiso:web.ver');
    Route::post('/redes-sociales', [RedSocialController::class, 'store'])
        ->middleware('permiso:web.crear');
    Route::get('/redes-sociales/{id}', [RedSocialController::class, 'show'])
        ->middleware('permiso:web.ver');
    Route::put('/redes-sociales/{id}', [RedSocialController::class, 'update'])
        ->middleware('permiso:web.editar');
    Route::delete('/redes-sociales/{id}', [RedSocialController::class, 'destroy'])
        ->middleware('permiso:web.eliminar');

    Route::get('/usuarios', [\App\Http\Controllers\Api\UsuarioController::class, 'index'])
        ->middleware('permiso:usuarios.ver');
    Route::post('/usuarios', [\App\Http\Controllers\Api\UsuarioController::class, 'store'])
        ->middleware('permiso:usuarios.crear');
    Route::get('/usuarios/{id}', [\App\Http\Controllers\Api\UsuarioController::class, 'show'])
        ->middleware('permiso:usuarios.ver');
    Route::put('/usuarios/{id}', [\App\Http\Controllers\Api\UsuarioController::class, 'update'])
        ->middleware('permiso:usuarios.editar');
    Route::delete('/usuarios/{id}', [\App\Http\Controllers\Api\UsuarioController::class, 'destroy'])
        ->middleware('permiso:usuarios.eliminar');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permiso:usuarios.ver');
    Route::get('/roles/{id}', [RoleController::class, 'show'])
        ->middleware('permiso:usuarios.ver');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permiso:usuarios.crear');
    Route::put('/roles/{id}', [RoleController::class, 'update'])
        ->middleware('permiso:usuarios.editar');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])
        ->middleware('permiso:usuarios.eliminar');
});

