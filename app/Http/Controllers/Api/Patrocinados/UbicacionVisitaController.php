<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CapturarUbicacionVisitaCommand;
use App\Application\Visitas\Handlers\CapturarUbicacionVisitaHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\CapturarUbicacionVisitaRequest;
use Illuminate\Http\JsonResponse;

class UbicacionVisitaController extends Controller
{
    public function __construct(
        private readonly CapturarUbicacionVisitaHandler $handler
    ) {}

    public function store(CapturarUbicacionVisitaRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->handler->handle(new CapturarUbicacionVisitaCommand(
            visitaId: $visitaId,
            dispositivoId: $request->dispositivo_id,
            tecnicoId: $request->tecnico_id,
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            precisionMetros: $request->precision_metros !== null ? (float) $request->precision_metros : null,
            fuente: $request->fuente,
        ));

        return response()->json($dto, 201);
    }
}
