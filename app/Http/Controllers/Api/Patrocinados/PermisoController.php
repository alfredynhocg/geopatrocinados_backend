<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\AccesoPatrocinados\Commands\CreatePermisoCommand;
use App\Application\AccesoPatrocinados\Commands\DeletePermisoCommand;
use App\Application\AccesoPatrocinados\Commands\UpdatePermisoCommand;
use App\Application\AccesoPatrocinados\Handlers\CreatePermisoHandler;
use App\Application\AccesoPatrocinados\Handlers\DeletePermisoHandler;
use App\Application\AccesoPatrocinados\Handlers\UpdatePermisoHandler;
use App\Application\AccesoPatrocinados\Queries\GetPermisosQuery;
use App\Application\AccesoPatrocinados\QueryHandlers\GetPermisosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\StorePermisoRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\UpdatePermisoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    public function __construct(
        private readonly GetPermisosQueryHandler $getPermisosHandler,
        private readonly CreatePermisoHandler $createHandler,
        private readonly UpdatePermisoHandler $updateHandler,
        private readonly DeletePermisoHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getPermisosHandler->handle(new GetPermisosQuery($pagination)));
    }

    public function store(StorePermisoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreatePermisoCommand(
            nombre: $request->nombre,
            modulo: $request->modulo,
            accion: $request->accion,
            descripcion: $request->descripcion,
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdatePermisoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdatePermisoCommand(
            id: $id,
            nombre: $request->nombre,
            modulo: $request->modulo,
            accion: $request->accion,
            descripcion: $request->descripcion,
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeletePermisoCommand($id));

        return response()->json(null, 204);
    }
}
