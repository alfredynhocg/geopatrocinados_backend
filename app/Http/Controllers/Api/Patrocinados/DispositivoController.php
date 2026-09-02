<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Dispositivos\Commands\AprobarDispositivoCommand;
use App\Application\Dispositivos\Commands\RegistrarDispositivoCommand;
use App\Application\Dispositivos\Commands\RevocarDispositivoCommand;
use App\Application\Dispositivos\Commands\UpdateDispositivoCommand;
use App\Application\Dispositivos\Handlers\AprobarDispositivoHandler;
use App\Application\Dispositivos\Handlers\RegistrarDispositivoHandler;
use App\Application\Dispositivos\Handlers\RevocarDispositivoHandler;
use App\Application\Dispositivos\Handlers\UpdateDispositivoHandler;
use App\Application\Dispositivos\Queries\GetDispositivoByIdQuery;
use App\Application\Dispositivos\Queries\GetDispositivosQuery;
use App\Application\Dispositivos\QueryHandlers\GetDispositivoByIdQueryHandler;
use App\Application\Dispositivos\QueryHandlers\GetDispositivosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Dispositivos\AprobarDispositivoRequest;
use App\Http\Requests\Patrocinados\Dispositivos\RegistrarDispositivoRequest;
use App\Http\Requests\Patrocinados\Dispositivos\RevocarDispositivoRequest;
use App\Http\Requests\Patrocinados\Dispositivos\UpdateDispositivoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispositivoController extends Controller
{
    public function __construct(
        private readonly GetDispositivosQueryHandler $getDispositivosHandler,
        private readonly GetDispositivoByIdQueryHandler $getDispositivoByIdHandler,
        private readonly RegistrarDispositivoHandler $registrarHandler,
        private readonly UpdateDispositivoHandler $updateHandler,
        private readonly AprobarDispositivoHandler $aprobarHandler,
        private readonly RevocarDispositivoHandler $revocarHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getDispositivosHandler->handle(new GetDispositivosQuery(
            pagination: $pagination,
            user_id: $request->get('user_id'),
            estado: $request->get('estado'),
        )));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->getDispositivoByIdHandler->handle(new GetDispositivoByIdQuery($id)));
    }

    public function store(RegistrarDispositivoRequest $request): JsonResponse
    {
        $dto = $this->registrarHandler->handle(new RegistrarDispositivoCommand(
            user_id: auth()->id(),
            identificador_dispositivo: $request->identificador_dispositivo,
            nombre_dispositivo: $request->nombre_dispositivo,
            plataforma: $request->plataforma,
            version_sistema: $request->version_sistema,
            version_aplicacion: $request->version_aplicacion,
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateDispositivoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateDispositivoCommand(
            id: $id,
            nombre_dispositivo: $request->nombre_dispositivo,
            version_sistema: $request->version_sistema,
            version_aplicacion: $request->version_aplicacion,
        ));

        return response()->json($dto);
    }

    public function aprobar(AprobarDispositivoRequest $request, string $id): JsonResponse
    {
        return response()->json($this->aprobarHandler->handle(new AprobarDispositivoCommand($id)));
    }

    public function revocar(RevocarDispositivoRequest $request, string $id): JsonResponse
    {
        return response()->json($this->revocarHandler->handle(new RevocarDispositivoCommand(
            id: $id,
            revoked_by: auth()->id(),
        )));
    }
}
