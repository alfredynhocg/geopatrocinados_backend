<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Geografia\Commands\CreateComunidadCommand;
use App\Application\Geografia\Commands\DeleteComunidadCommand;
use App\Application\Geografia\Commands\UpdateComunidadCommand;
use App\Application\Geografia\Handlers\CreateComunidadHandler;
use App\Application\Geografia\Handlers\DeleteComunidadHandler;
use App\Application\Geografia\Handlers\UpdateComunidadHandler;
use App\Application\Geografia\Queries\GetComunidadesQuery;
use App\Application\Geografia\QueryHandlers\GetComunidadesQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Geografia\StoreComunidadRequest;
use App\Http\Requests\Patrocinados\Geografia\UpdateComunidadRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComunidadController extends Controller
{
    public function __construct(
        private readonly GetComunidadesQueryHandler $getComunidadesHandler,
        private readonly CreateComunidadHandler $createHandler,
        private readonly UpdateComunidadHandler $updateHandler,
        private readonly DeleteComunidadHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getComunidadesHandler->handle(new GetComunidadesQuery(
            pagination: $pagination,
            municipio_id: $request->get('municipio_id'),
        )));
    }

    public function store(StoreComunidadRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateComunidadCommand(
            municipio_id: $request->municipio_id,
            codigo: $request->codigo,
            comunidad: $request->comunidad,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateComunidadRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateComunidadCommand(
            id: $id,
            municipio_id: $request->municipio_id,
            codigo: $request->codigo,
            comunidad: $request->comunidad,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteComunidadCommand($id));

        return response()->json(null, 204);
    }
}
