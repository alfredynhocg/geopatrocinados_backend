<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\SubirFotoVisitaCommand;
use App\Application\Visitas\Handlers\SubirFotoVisitaHandler;
use App\Application\Visitas\Services\FotoVisitaService;
use App\Domain\Visitas\Contracts\FotoVisitaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\SubirFotoVisitaRequest;
use Illuminate\Http\JsonResponse;

class FotoVisitaController extends Controller
{
    public function __construct(
        private readonly FotoVisitaRepositoryInterface $repository,
        private readonly SubirFotoVisitaHandler $handler,
        private readonly FotoVisitaService $fotoService,
    ) {}

    public function store(SubirFotoVisitaRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->handler->handle(new SubirFotoVisitaCommand(
            visitaId: $visitaId,
            dispositivoId: $request->dispositivo_id,
            archivo: $request->file('archivo'),
            latitude: $request->latitude !== null ? (float) $request->latitude : null,
            longitude: $request->longitude !== null ? (float) $request->longitude : null,
        ));

        return response()->json($dto, 201);
    }

    public function show(string $visitaId, string $fotoId): JsonResponse
    {
        $model = $this->repository->findById($fotoId);

        return response()->json(
            \App\Application\Visitas\DTOs\FotoVisitaDTO::fromModel($model, $this->fotoService->urlFirmada($model->clave_almacenamiento))
        );
    }
}
