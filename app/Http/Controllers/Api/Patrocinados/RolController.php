<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\AccesoPatrocinados\Commands\AsignarPermisoARolCommand;
use App\Application\AccesoPatrocinados\Commands\CreateRolCommand;
use App\Application\AccesoPatrocinados\Commands\DeleteRolCommand;
use App\Application\AccesoPatrocinados\Commands\RevocarPermisoDeRolCommand;
use App\Application\AccesoPatrocinados\Commands\UpdateRolCommand;
use App\Application\AccesoPatrocinados\Handlers\AsignarPermisoARolHandler;
use App\Application\AccesoPatrocinados\Handlers\CreateRolHandler;
use App\Application\AccesoPatrocinados\Handlers\DeleteRolHandler;
use App\Application\AccesoPatrocinados\Handlers\RevocarPermisoDeRolHandler;
use App\Application\AccesoPatrocinados\Handlers\UpdateRolHandler;
use App\Application\AccesoPatrocinados\Queries\GetRolesQuery;
use App\Application\AccesoPatrocinados\QueryHandlers\GetRolesQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\AsignarPermisoRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\StoreRolRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\UpdateRolRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function __construct(
        private readonly GetRolesQueryHandler $getRolesHandler,
        private readonly CreateRolHandler $createHandler,
        private readonly UpdateRolHandler $updateHandler,
        private readonly DeleteRolHandler $deleteHandler,
        private readonly AsignarPermisoARolHandler $asignarPermisoHandler,
        private readonly RevocarPermisoDeRolHandler $revocarPermisoHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getRolesHandler->handle(new GetRolesQuery($pagination)));
    }

    public function store(StoreRolRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateRolCommand(
            nombre: $request->nombre,
            descripcion: $request->descripcion,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateRolRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateRolCommand(
            id: $id,
            nombre: $request->nombre,
            descripcion: $request->descripcion,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteRolCommand($id));

        return response()->json(null, 204);
    }

    public function asignarPermiso(AsignarPermisoRequest $request, string $id): JsonResponse
    {
        $this->asignarPermisoHandler->handle(new AsignarPermisoARolCommand(
            rol_id: $id,
            permiso_id: $request->permiso_id,
        ));

        return response()->json(['status' => 'ok']);
    }

    public function revocarPermiso(string $id, string $permisoId): JsonResponse
    {
        $this->revocarPermisoHandler->handle(new RevocarPermisoDeRolCommand(rol_id: $id, permiso_id: $permisoId));

        return response()->json(['status' => 'ok']);
    }
}
