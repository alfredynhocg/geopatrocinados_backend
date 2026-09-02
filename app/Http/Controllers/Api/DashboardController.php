<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalUsuarios   = DB::table('usuarios')->where('activo', true)->count();
        $totalProgramas  = DB::table('t_programa')->where('estado', 1)->count();
        $totalInscripciones = DB::table('t_inscripcion')->whereMonth('fecha_reg', now()->month)->count();
        $totalCertificados  = DB::table('t_certificado')->count();
        $mensajesNuevos     = DB::table('web_contacto_mensaje')
            ->where('estado', 'nuevo')
            ->count();
        return response()->json([
            'resumen' => [
                'total_usuarios'        => $totalUsuarios,
                'total_programas'       => $totalProgramas,
                'inscripciones_mes'     => $totalInscripciones,
                'total_certificados'    => $totalCertificados,
                'mensajes_nuevos'       => $mensajesNuevos,
            ],
        ]);
    }
}
