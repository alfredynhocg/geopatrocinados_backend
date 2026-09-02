<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Geografia\Commands\CreateUbicacionCommand;
use App\Application\Geografia\Commands\DeleteUbicacionCommand;
use App\Application\Geografia\Commands\UpdateUbicacionCommand;
use App\Application\Geografia\Handlers\CreateUbicacionHandler;
use App\Application\Geografia\Handlers\DeleteUbicacionHandler;
use App\Application\Geografia\Handlers\UpdateUbicacionHandler;
use App\Application\Geografia\Queries\GetUbicacionesQuery;
use App\Application\Geografia\QueryHandlers\GetUbicacionesQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Geografia\StoreUbicacionRequest;
use App\Http\Requests\Patrocinados\Geografia\UpdateUbicacionRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    public function __construct(
        private readonly GetUbicacionesQueryHandler $getUbicacionesHandler,
        private readonly CreateUbicacionHandler $createHandler,
        private readonly UpdateUbicacionHandler $updateHandler,
        private readonly DeleteUbicacionHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getUbicacionesHandler->handle(new GetUbicacionesQuery(
            pagination: $pagination,
            comunidad_id: $request->get('comunidad_id'),
        )));
    }

    public function store(StoreUbicacionRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateUbicacionCommand(
            comunidad_id: $request->comunidad_id,
            nombre: $request->nombre,
            tipo: $request->tipo,
            direccion: $request->direccion,
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            precision_metros: $request->precision_metros !== null ? (float) $request->precision_metros : null,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateUbicacionRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateUbicacionCommand(
            id: $id,
            comunidad_id: $request->comunidad_id,
            nombre: $request->nombre,
            tipo: $request->tipo,
            direccion: $request->direccion,
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            precision_metros: $request->precision_metros !== null ? (float) $request->precision_metros : null,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteUbicacionCommand($id));

        return response()->json(null, 204);
    }
}
