<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Geografia\Commands\CreateMunicipioCommand;
use App\Application\Geografia\Commands\DeleteMunicipioCommand;
use App\Application\Geografia\Commands\UpdateMunicipioCommand;
use App\Application\Geografia\Handlers\CreateMunicipioHandler;
use App\Application\Geografia\Handlers\DeleteMunicipioHandler;
use App\Application\Geografia\Handlers\UpdateMunicipioHandler;
use App\Application\Geografia\Queries\GetMunicipiosQuery;
use App\Application\Geografia\QueryHandlers\GetMunicipiosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Geografia\StoreMunicipioRequest;
use App\Http\Requests\Patrocinados\Geografia\UpdateMunicipioRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MunicipioController extends Controller
{
    public function __construct(
        private readonly GetMunicipiosQueryHandler $getMunicipiosHandler,
        private readonly CreateMunicipioHandler $createHandler,
        private readonly UpdateMunicipioHandler $updateHandler,
        private readonly DeleteMunicipioHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getMunicipiosHandler->handle(new GetMunicipiosQuery(
            pagination: $pagination,
            departamento_id: $request->get('departamento_id'),
        )));
    }

    public function store(StoreMunicipioRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateMunicipioCommand(
            departamento_id: $request->departamento_id,
            codigo: $request->codigo,
            municipio: $request->municipio,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateMunicipioRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateMunicipioCommand(
            id: $id,
            departamento_id: $request->departamento_id,
            codigo: $request->codigo,
            municipio: $request->municipio,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteMunicipioCommand($id));

        return response()->json(null, 204);
    }
}
