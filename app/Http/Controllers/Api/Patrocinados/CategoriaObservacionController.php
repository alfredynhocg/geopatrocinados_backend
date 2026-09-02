<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CreateCategoriaObservacionCommand;
use App\Application\Visitas\Commands\DeleteCategoriaObservacionCommand;
use App\Application\Visitas\Commands\UpdateCategoriaObservacionCommand;
use App\Application\Visitas\Handlers\CreateCategoriaObservacionHandler;
use App\Application\Visitas\Handlers\DeleteCategoriaObservacionHandler;
use App\Application\Visitas\Handlers\UpdateCategoriaObservacionHandler;
use App\Application\Visitas\Queries\GetCategoriasObservacionesQuery;
use App\Application\Visitas\QueryHandlers\GetCategoriasObservacionesQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\StoreCategoriaObservacionRequest;
use App\Http\Requests\Patrocinados\Visitas\UpdateCategoriaObservacionRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaObservacionController extends Controller
{
    public function __construct(
        private readonly GetCategoriasObservacionesQueryHandler $getHandler,
        private readonly CreateCategoriaObservacionHandler $createHandler,
        private readonly UpdateCategoriaObservacionHandler $updateHandler,
        private readonly DeleteCategoriaObservacionHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray([
            'pageIndex' => $request->get('pageIndex', 1),
            'pageSize'  => $request->get('pageSize', 15),
            'query'     => $request->get('query', ''),
        ]);

        return response()->json($this->getHandler->handle(new GetCategoriasObservacionesQuery($pagination)));
    }

    public function store(StoreCategoriaObservacionRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateCategoriaObservacionCommand(
            codigo: $request->codigo,
            categoriaObservaciones: $request->categoria_observaciones,
            descripcion: $request->descripcion,
            updatedBy: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateCategoriaObservacionRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateCategoriaObservacionCommand(
            id: $id,
            codigo: $request->codigo,
            categoriaObservaciones: $request->categoria_observaciones,
            descripcion: $request->descripcion,
            estado: $request->boolean('estado', true),
            updatedBy: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteCategoriaObservacionCommand($id));

        return response()->json(null, 204);
    }
}
