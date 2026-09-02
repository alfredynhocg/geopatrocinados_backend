<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Geografia\Commands\CreateDepartamentoCommand;
use App\Application\Geografia\Commands\DeleteDepartamentoCommand;
use App\Application\Geografia\Commands\UpdateDepartamentoCommand;
use App\Application\Geografia\Handlers\CreateDepartamentoHandler;
use App\Application\Geografia\Handlers\DeleteDepartamentoHandler;
use App\Application\Geografia\Handlers\UpdateDepartamentoHandler;
use App\Application\Geografia\Queries\GetDepartamentosQuery;
use App\Application\Geografia\QueryHandlers\GetDepartamentosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Geografia\StoreDepartamentoRequest;
use App\Http\Requests\Patrocinados\Geografia\UpdateDepartamentoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    public function __construct(
        private readonly GetDepartamentosQueryHandler $getDepartamentosHandler,
        private readonly CreateDepartamentoHandler $createHandler,
        private readonly UpdateDepartamentoHandler $updateHandler,
        private readonly DeleteDepartamentoHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getDepartamentosHandler->handle(new GetDepartamentosQuery($pagination)));
    }

    public function store(StoreDepartamentoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateDepartamentoCommand(
            codigo: $request->codigo,
            departamento: $request->departamento,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateDepartamentoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateDepartamentoCommand(
            id: $id,
            codigo: $request->codigo,
            departamento: $request->departamento,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteDepartamentoCommand($id));

        return response()->json(null, 204);
    }
}
