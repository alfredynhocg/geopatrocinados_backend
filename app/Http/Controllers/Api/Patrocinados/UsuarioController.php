<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\AccesoPatrocinados\Commands\AsignarRolCommand;
use App\Application\AccesoPatrocinados\Commands\CreateUsuarioCommand;
use App\Application\AccesoPatrocinados\Commands\DeleteUsuarioCommand;
use App\Application\AccesoPatrocinados\Commands\RevocarRolCommand;
use App\Application\AccesoPatrocinados\Commands\UpdateUsuarioCommand;
use App\Application\AccesoPatrocinados\Handlers\AsignarRolHandler;
use App\Application\AccesoPatrocinados\Handlers\CreateUsuarioHandler;
use App\Application\AccesoPatrocinados\Handlers\DeleteUsuarioHandler;
use App\Application\AccesoPatrocinados\Handlers\RevocarRolHandler;
use App\Application\AccesoPatrocinados\Handlers\UpdateUsuarioHandler;
use App\Application\AccesoPatrocinados\Queries\GetUsuarioByIdQuery;
use App\Application\AccesoPatrocinados\Queries\GetUsuariosQuery;
use App\Application\AccesoPatrocinados\QueryHandlers\GetUsuarioByIdQueryHandler;
use App\Application\AccesoPatrocinados\QueryHandlers\GetUsuariosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\AsignarRolRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\StoreUsuarioRequest;
use App\Http\Requests\Patrocinados\AccesoPatrocinados\UpdateUsuarioRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly GetUsuariosQueryHandler $getUsuariosHandler,
        private readonly GetUsuarioByIdQueryHandler $getUsuarioByIdHandler,
        private readonly CreateUsuarioHandler $createHandler,
        private readonly UpdateUsuarioHandler $updateHandler,
        private readonly DeleteUsuarioHandler $deleteHandler,
        private readonly AsignarRolHandler $asignarRolHandler,
        private readonly RevocarRolHandler $revocarRolHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getUsuariosHandler->handle(new GetUsuariosQuery($pagination)));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->getUsuarioByIdHandler->handle(new GetUsuarioByIdQuery($id)));
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateUsuarioCommand(
            username: $request->username,
            email: $request->email,
            password: $request->password,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            telefono: $request->telefono,
            estado: $request->estado ?? 'ACTIVO',
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateUsuarioRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateUsuarioCommand(
            id: $id,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            telefono: $request->telefono,
            estado: $request->estado,
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteUsuarioCommand($id));

        return response()->json(null, 204);
    }

    public function asignarRol(AsignarRolRequest $request, string $id): JsonResponse
    {
        $this->asignarRolHandler->handle(new AsignarRolCommand(
            usuario_id: $id,
            rol_id: $request->rol_id,
            asignado_por: auth()->id(),
        ));

        return response()->json(['status' => 'ok']);
    }

    public function revocarRol(string $id, string $rolId): JsonResponse
    {
        $this->revocarRolHandler->handle(new RevocarRolCommand(usuario_id: $id, rol_id: $rolId));

        return response()->json(['status' => 'ok']);
    }
}
