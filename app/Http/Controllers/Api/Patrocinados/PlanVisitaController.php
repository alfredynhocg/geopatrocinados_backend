<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CreatePlanVisitaCommand;
use App\Application\Visitas\Commands\DeletePlanVisitaCommand;
use App\Application\Visitas\Commands\UpdatePlanVisitaCommand;
use App\Application\Visitas\Handlers\CreatePlanVisitaHandler;
use App\Application\Visitas\Handlers\DeletePlanVisitaHandler;
use App\Application\Visitas\Handlers\UpdatePlanVisitaHandler;
use App\Application\Visitas\Queries\GetPlanesVisitaQuery;
use App\Application\Visitas\QueryHandlers\GetPlanesVisitaQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\StorePlanVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\UpdatePlanVisitaRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanVisitaController extends Controller
{
    public function __construct(
        private readonly GetPlanesVisitaQueryHandler $getHandler,
        private readonly CreatePlanVisitaHandler $createHandler,
        private readonly UpdatePlanVisitaHandler $updateHandler,
        private readonly DeletePlanVisitaHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray([
            'pageIndex' => $request->get('pageIndex', 1),
            'pageSize'  => $request->get('pageSize', 15),
            'query'     => $request->get('query', ''),
        ]);

        return response()->json($this->getHandler->handle(new GetPlanesVisitaQuery($pagination)));
    }

    public function store(StorePlanVisitaRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreatePlanVisitaCommand(
            plan: $request->plan,
            anio: (int) $request->anio,
            fechaInicio: $request->fecha_inicio,
            fechaFin: $request->fecha_fin,
            createdBy: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdatePlanVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdatePlanVisitaCommand(
            id: $id,
            plan: $request->plan,
            anio: (int) $request->anio,
            fechaInicio: $request->fecha_inicio,
            fechaFin: $request->fecha_fin,
            estado: $request->estado,
            updatedBy: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeletePlanVisitaCommand($id));

        return response()->json(null, 204);
    }
}
