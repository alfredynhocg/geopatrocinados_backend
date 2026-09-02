<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CreateMotivoVisitaCommand;
use App\Application\Visitas\Commands\DeleteMotivoVisitaCommand;
use App\Application\Visitas\Commands\UpdateMotivoVisitaCommand;
use App\Application\Visitas\Handlers\CreateMotivoVisitaHandler;
use App\Application\Visitas\Handlers\DeleteMotivoVisitaHandler;
use App\Application\Visitas\Handlers\UpdateMotivoVisitaHandler;
use App\Application\Visitas\Queries\GetMotivosVisitasQuery;
use App\Application\Visitas\QueryHandlers\GetMotivosVisitasQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\StoreMotivoVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\UpdateMotivoVisitaRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MotivoVisitaController extends Controller
{
    public function __construct(
        private readonly GetMotivosVisitasQueryHandler $getHandler,
        private readonly CreateMotivoVisitaHandler $createHandler,
        private readonly UpdateMotivoVisitaHandler $updateHandler,
        private readonly DeleteMotivoVisitaHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray([
            'pageIndex' => $request->get('pageIndex', 1),
            'pageSize'  => $request->get('pageSize', 15),
            'query'     => $request->get('query', ''),
        ]);

        return response()->json($this->getHandler->handle(new GetMotivosVisitasQuery($pagination)));
    }

    public function store(StoreMotivoVisitaRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateMotivoVisitaCommand(
            motivoVisita: $request->motivo_visita,
            descripcion:  $request->descripcion,
            updatedBy:    auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateMotivoVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateMotivoVisitaCommand(
            id:           $id,
            motivoVisita: $request->motivo_visita,
            descripcion:  $request->descripcion,
            estado:       $request->boolean('estado', true),
            updatedBy:    auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteMotivoVisitaCommand($id));
        return response()->json(null, 204);
    }
}
