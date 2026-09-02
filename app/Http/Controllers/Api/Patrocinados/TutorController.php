<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Patrocinados\Commands\CreateTutorCommand;
use App\Application\Patrocinados\Commands\DeleteTutorCommand;
use App\Application\Patrocinados\Commands\UpdateTutorCommand;
use App\Application\Patrocinados\Handlers\CreateTutorHandler;
use App\Application\Patrocinados\Handlers\DeleteTutorHandler;
use App\Application\Patrocinados\Handlers\UpdateTutorHandler;
use App\Application\Patrocinados\Queries\GetTutoresByPatrocinadoQuery;
use App\Application\Patrocinados\QueryHandlers\GetTutoresByPatrocinadoQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Patrocinados\StoreTutorRequest;
use App\Http\Requests\Patrocinados\Patrocinados\UpdateTutorRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function __construct(
        private readonly GetTutoresByPatrocinadoQueryHandler $getTutoresHandler,
        private readonly CreateTutorHandler $createHandler,
        private readonly UpdateTutorHandler $updateHandler,
        private readonly DeleteTutorHandler $deleteHandler,
    ) {}

    public function index(Request $request, string $patrocinadoId): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getTutoresHandler->handle(
            new GetTutoresByPatrocinadoQuery($patrocinadoId, $pagination)
        ));
    }

    public function store(StoreTutorRequest $request, string $patrocinadoId): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateTutorCommand(
            patrocinado_id: $patrocinadoId,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            tipo_parentesco_id: $request->tipo_parentesco_id,
            telefono: $request->telefono,
            direccion: $request->direccion,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateTutorRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateTutorCommand(
            id: $id,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            tipo_parentesco_id: $request->tipo_parentesco_id,
            telefono: $request->telefono,
            direccion: $request->direccion,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteTutorCommand($id));

        return response()->json(null, 204);
    }
}
