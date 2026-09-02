<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\HabilitarDispositivoParaVisitaCommand;
use App\Application\Visitas\Commands\RevocarHabilitacionVisitaCommand;
use App\Application\Visitas\Handlers\HabilitarDispositivoParaVisitaHandler;
use App\Application\Visitas\Handlers\RevocarHabilitacionVisitaHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\HabilitarDispositivoRequest;
use Illuminate\Http\JsonResponse;

class HabilitacionVisitaController extends Controller
{
    public function __construct(
        private readonly HabilitarDispositivoParaVisitaHandler $habilitarHandler,
        private readonly RevocarHabilitacionVisitaHandler $revocarHandler,
    ) {}

    public function store(HabilitarDispositivoRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->habilitarHandler->handle(new HabilitarDispositivoParaVisitaCommand(
            visitaId: $visitaId,
            tecnicoId: $request->tecnico_id,
            dispositivoId: $request->dispositivo_id,
            authorizedBy: auth()->id(),
            fechaExpiracion: new \DateTimeImmutable($request->fecha_expiracion),
        ));

        return response()->json($dto, 201);
    }

    public function revocar(string $id): JsonResponse
    {
        $dto = $this->revocarHandler->handle(new RevocarHabilitacionVisitaCommand(
            habilitacionId: $id,
            revokedBy: auth()->id(),
        ));

        return response()->json($dto);
    }
}
