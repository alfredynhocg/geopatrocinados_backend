<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CreateObservacionVisitaCommand;
use App\Application\Visitas\Handlers\CreateObservacionVisitaHandler;
use App\Domain\Visitas\Contracts\ObservacionVisitaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\StoreObservacionVisitaRequest;
use Illuminate\Http\JsonResponse;

class ObservacionVisitaController extends Controller
{
    public function __construct(
        private readonly ObservacionVisitaRepositoryInterface $repository,
        private readonly CreateObservacionVisitaHandler $handler,
    ) {}

    public function index(string $visitaId): JsonResponse
    {
        return response()->json(['data' => $this->repository->listarPorVisita($visitaId)]);
    }

    public function store(StoreObservacionVisitaRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->handler->handle(new CreateObservacionVisitaCommand(
            visitaId: $visitaId,
            dispositivoId: $request->dispositivo_id,
            categoriaId: $request->categoria_id,
            tipo: $request->tipo,
            observacion: $request->observacion,
            createdBy: auth()->id(),
        ));

        return response()->json($dto, 201);
    }
}
