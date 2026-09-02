<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\RevisarVisitaCommand;
use App\Application\Visitas\Handlers\RevisarVisitaHandler;
use App\Application\Visitas\Queries\GetRevisionesVisitaQuery;
use App\Application\Visitas\QueryHandlers\GetRevisionesVisitaQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\StoreRevisionVisitaRequest;
use Illuminate\Http\JsonResponse;

class RevisionVisitaController extends Controller
{
    public function __construct(
        private readonly GetRevisionesVisitaQueryHandler $getHandler,
        private readonly RevisarVisitaHandler $revisarHandler,
    ) {}

    public function index(string $visitaId): JsonResponse
    {
        return response()->json(['data' => $this->getHandler->handle(new GetRevisionesVisitaQuery($visitaId))]);
    }

    public function store(StoreRevisionVisitaRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->revisarHandler->handle(new RevisarVisitaCommand(
            visitaId: $visitaId,
            userId: auth()->id(),
            estado: $request->estado,
            comentarios: $request->comentarios,
        ));

        return response()->json($dto, 201);
    }
}
