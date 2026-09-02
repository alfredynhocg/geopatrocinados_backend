<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Patrocinados\Commands\CreateEstadoPatrocinadoCommand;
use App\Application\Patrocinados\Commands\DeleteEstadoPatrocinadoCommand;
use App\Application\Patrocinados\Commands\UpdateEstadoPatrocinadoCommand;
use App\Application\Patrocinados\Handlers\CreateEstadoPatrocinadoHandler;
use App\Application\Patrocinados\Handlers\DeleteEstadoPatrocinadoHandler;
use App\Application\Patrocinados\Handlers\UpdateEstadoPatrocinadoHandler;
use App\Application\Patrocinados\Queries\GetEstadosPatrocinadoQuery;
use App\Application\Patrocinados\QueryHandlers\GetEstadosPatrocinadoQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Patrocinados\StoreEstadoPatrocinadoRequest;
use App\Http\Requests\Patrocinados\Patrocinados\UpdateEstadoPatrocinadoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstadoPatrocinadoController extends Controller
{
    public function __construct(
        private readonly GetEstadosPatrocinadoQueryHandler $getEstadosHandler,
        private readonly CreateEstadoPatrocinadoHandler $createHandler,
        private readonly UpdateEstadoPatrocinadoHandler $updateHandler,
        private readonly DeleteEstadoPatrocinadoHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getEstadosHandler->handle(new GetEstadosPatrocinadoQuery($pagination)));
    }

    public function store(StoreEstadoPatrocinadoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateEstadoPatrocinadoCommand(estado: $request->estado));

        return response()->json($dto, 201);
    }

    public function update(UpdateEstadoPatrocinadoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateEstadoPatrocinadoCommand(id: $id, estado: $request->estado));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteEstadoPatrocinadoCommand($id));

        return response()->json(null, 204);
    }
}
