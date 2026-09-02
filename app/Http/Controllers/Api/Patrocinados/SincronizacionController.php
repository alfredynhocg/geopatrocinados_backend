<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Sincronizacion\Commands\CerrarLoteSincronizacionCommand;
use App\Application\Sincronizacion\Commands\IniciarLoteSincronizacionCommand;
use App\Application\Sincronizacion\Commands\ProcesarElementoSincronizacionCommand;
use App\Application\Sincronizacion\Handlers\CerrarLoteSincronizacionHandler;
use App\Application\Sincronizacion\Handlers\IniciarLoteSincronizacionHandler;
use App\Application\Sincronizacion\Handlers\ProcesarElementoSincronizacionHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Sincronizacion\IniciarLoteRequest;
use App\Http\Requests\Patrocinados\Sincronizacion\ProcesarElementoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SincronizacionController extends Controller
{
    public function __construct(
        private readonly IniciarLoteSincronizacionHandler $iniciarLoteHandler,
        private readonly ProcesarElementoSincronizacionHandler $procesarElementoHandler,
        private readonly CerrarLoteSincronizacionHandler $cerrarLoteHandler,
    ) {}

    public function iniciarLote(IniciarLoteRequest $request): JsonResponse
    {
        $dto = $this->iniciarLoteHandler->handle(new IniciarLoteSincronizacionCommand(
            dispositivo_id: $request->dispositivo_id,
            user_id: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function procesarElemento(ProcesarElementoRequest $request, string $loteId): JsonResponse
    {
        // Cada elemento se procesa y reporta su propio resultado — nunca aborta
        // el resto del lote (best-effort, no todo-o-nada).
        $dto = $this->procesarElementoHandler->handle(new ProcesarElementoSincronizacionCommand(
            lote_id: $loteId,
            tipo_entidad: $request->tipo_entidad,
            entidad_id: $request->entidad_id,
            operacion: $request->operacion,
            hash_datos: $request->hash_datos,
            payload: $request->payload ?? [],
        ));

        return response()->json($dto);
    }

    public function cerrarLote(Request $request, string $loteId): JsonResponse
    {
        $dto = $this->cerrarLoteHandler->handle(new CerrarLoteSincronizacionCommand(
            lote_id: $loteId,
            registros_enviados: (int) $request->input('registros_enviados', 0),
            registros_recibidos: (int) $request->input('registros_recibidos', 0),
        ));

        return response()->json($dto);
    }
}
