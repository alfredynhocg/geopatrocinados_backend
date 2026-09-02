<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Patrocinados\Commands\CreateTipoParentescoCommand;
use App\Application\Patrocinados\Commands\DeleteTipoParentescoCommand;
use App\Application\Patrocinados\Commands\UpdateTipoParentescoCommand;
use App\Application\Patrocinados\Handlers\CreateTipoParentescoHandler;
use App\Application\Patrocinados\Handlers\DeleteTipoParentescoHandler;
use App\Application\Patrocinados\Handlers\UpdateTipoParentescoHandler;
use App\Application\Patrocinados\Queries\GetTiposParentescoQuery;
use App\Application\Patrocinados\QueryHandlers\GetTiposParentescoQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Patrocinados\StoreTipoParentescoRequest;
use App\Http\Requests\Patrocinados\Patrocinados\UpdateTipoParentescoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipoParentescoController extends Controller
{
    public function __construct(
        private readonly GetTiposParentescoQueryHandler $getTiposHandler,
        private readonly CreateTipoParentescoHandler $createHandler,
        private readonly UpdateTipoParentescoHandler $updateHandler,
        private readonly DeleteTipoParentescoHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getTiposHandler->handle(new GetTiposParentescoQuery($pagination)));
    }

    public function store(StoreTipoParentescoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateTipoParentescoCommand(
            parentesco: $request->parentesco,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateTipoParentescoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateTipoParentescoCommand(
            id: $id,
            parentesco: $request->parentesco,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteTipoParentescoCommand($id));

        return response()->json(null, 204);
    }
}
