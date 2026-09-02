<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Patrocinados\Commands\CambiarUbicacionPatrocinadoCommand;
use App\Application\Patrocinados\Commands\CreatePatrocinadoCommand;
use App\Application\Patrocinados\Commands\DeletePatrocinadoCommand;
use App\Application\Patrocinados\Commands\UpdatePatrocinadoCommand;
use App\Application\Patrocinados\Handlers\CambiarUbicacionPatrocinadoHandler;
use App\Application\Patrocinados\Handlers\CreatePatrocinadoHandler;
use App\Application\Patrocinados\Handlers\DeletePatrocinadoHandler;
use App\Application\Patrocinados\Handlers\UpdatePatrocinadoHandler;
use App\Application\Patrocinados\Queries\GetHistorialUbicacionesQuery;
use App\Application\Patrocinados\Queries\GetPatrocinadoByIdQuery;
use App\Application\Patrocinados\Queries\GetPatrocinadosQuery;
use App\Application\Patrocinados\QueryHandlers\GetHistorialUbicacionesQueryHandler;
use App\Application\Patrocinados\QueryHandlers\GetPatrocinadoByIdQueryHandler;
use App\Application\Patrocinados\QueryHandlers\GetPatrocinadosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Patrocinados\CambiarUbicacionPatrocinadoRequest;
use App\Http\Requests\Patrocinados\Patrocinados\StorePatrocinadoRequest;
use App\Http\Requests\Patrocinados\Patrocinados\UpdatePatrocinadoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatrocinadoController extends Controller
{
    public function __construct(
        private readonly GetPatrocinadosQueryHandler $getPatrocinadosHandler,
        private readonly GetPatrocinadoByIdQueryHandler $getPatrocinadoByIdHandler,
        private readonly GetHistorialUbicacionesQueryHandler $getHistorialHandler,
        private readonly CreatePatrocinadoHandler $createHandler,
        private readonly UpdatePatrocinadoHandler $updateHandler,
        private readonly DeletePatrocinadoHandler $deleteHandler,
        private readonly CambiarUbicacionPatrocinadoHandler $cambiarUbicacionHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getPatrocinadosHandler->handle(new GetPatrocinadosQuery(
            pagination: $pagination,
            comunidad_id: $request->get('comunidad_id'),
            estado_id: $request->get('estado_id'),
            nivel_educativo: $request->get('nivel_educativo'),
        )));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->getPatrocinadoByIdHandler->handle(new GetPatrocinadoByIdQuery($id)));
    }

    public function store(StorePatrocinadoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreatePatrocinadoCommand(
            codigo: $request->codigo,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            fecha_nacimiento: $request->fecha_nacimiento,
            sexo: $request->sexo,
            comunidad_id: $request->comunidad_id,
            ubicacion_id: $request->ubicacion_id,
            unidad_educativa: $request->unidad_educativa,
            nivel_educativo: $request->nivel_educativo,
            estado_id: $request->estado_id,
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdatePatrocinadoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdatePatrocinadoCommand(
            id: $id,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            fecha_nacimiento: $request->fecha_nacimiento,
            sexo: $request->sexo,
            unidad_educativa: $request->unidad_educativa,
            nivel_educativo: $request->nivel_educativo,
            estado_id: $request->estado_id,
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeletePatrocinadoCommand($id));

        return response()->json(null, 204);
    }

    public function cambiarUbicacion(CambiarUbicacionPatrocinadoRequest $request, string $id): JsonResponse
    {
        $dto = $this->cambiarUbicacionHandler->handle(new CambiarUbicacionPatrocinadoCommand(
            patrocinado_id: $id,
            comunidad_id: $request->comunidad_id,
            ubicacion_id: $request->ubicacion_id,
            usuario_id: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function historialUbicaciones(string $id): JsonResponse
    {
        return response()->json($this->getHistorialHandler->handle(new GetHistorialUbicacionesQuery($id)));
    }
}
