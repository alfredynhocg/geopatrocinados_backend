<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CreateVisitaCommand;
use App\Application\Visitas\Commands\FinalizarVisitaCommand;
use App\Application\Visitas\Commands\IniciarVisitaCommand;
use App\Application\Visitas\Commands\ReasignarVisitaCommand;
use App\Application\Visitas\Commands\ReprogramarVisitaCommand;
use App\Application\Visitas\Commands\UpdateVisitaCommand;
use App\Application\Visitas\Handlers\CreateVisitaHandler;
use App\Application\Visitas\Handlers\FinalizarVisitaHandler;
use App\Application\Visitas\Handlers\IniciarVisitaHandler;
use App\Application\Visitas\Handlers\ReasignarVisitaHandler;
use App\Application\Visitas\Handlers\ReprogramarVisitaHandler;
use App\Application\Visitas\Handlers\UpdateVisitaHandler;
use App\Application\Visitas\Queries\GetVisitaByIdQuery;
use App\Application\Visitas\Queries\GetVisitasQuery;
use App\Application\Visitas\QueryHandlers\GetVisitaByIdQueryHandler;
use App\Application\Visitas\QueryHandlers\GetVisitasQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\FinalizarVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\IniciarVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\ReasignarVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\StoreVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\UpdateVisitaRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitaController extends Controller
{
    public function __construct(
        private readonly GetVisitasQueryHandler $getVisitasHandler,
        private readonly GetVisitaByIdQueryHandler $getVisitaByIdHandler,
        private readonly CreateVisitaHandler $createHandler,
        private readonly UpdateVisitaHandler $updateHandler,
        private readonly IniciarVisitaHandler $iniciarHandler,
        private readonly FinalizarVisitaHandler $finalizarHandler,
        private readonly ReprogramarVisitaHandler $reprogramarHandler,
        private readonly ReasignarVisitaHandler $reasignarHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray([
            'pageIndex' => $request->get('pageIndex', 1),
            'pageSize'  => $request->get('pageSize', 15),
            'query'     => $request->get('query', ''),
        ]);

        return response()->json($this->getVisitasHandler->handle(new GetVisitasQuery(
            pagination: $pagination,
            patrocinadoId: $request->get('patrocinado_id'),
            tecnicoId: $request->get('tecnico_id'),
            estado: $request->get('estado'),
            desde: $request->get('desde'),
            hasta: $request->get('hasta'),
        )));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->getVisitaByIdHandler->handle(new GetVisitaByIdQuery($id)));
    }

    public function store(StoreVisitaRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateVisitaCommand(
            planVisitaId: $request->plan_visita_id,
            patrocinadoId: $request->patrocinado_id,
            userId: $request->user_id,
            motivoVisitaId: $request->motivo_visita_id,
            fechaProgramada: $request->fecha_programada,
            createdBy: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateVisitaCommand(
            id: $id,
            planVisitaId: $request->plan_visita_id,
            motivoVisitaId: $request->motivo_visita_id,
            fechaProgramada: $request->fecha_programada,
        ));

        return response()->json($dto);
    }

    public function iniciar(IniciarVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->iniciarHandler->handle(new IniciarVisitaCommand(
            visitaId: $id,
            dispositivoId: $request->dispositivo_id,
            ejecutadoPor: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function finalizar(FinalizarVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->finalizarHandler->handle(new FinalizarVisitaCommand(
            visitaId: $id,
            estadoFinal: $request->estado_final,
            ejecutadoPor: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function reprogramar(string $id): JsonResponse
    {
        $dto = $this->reprogramarHandler->handle(new ReprogramarVisitaCommand(
            visitaId: $id,
            ejecutadoPor: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function reasignar(ReasignarVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->reasignarHandler->handle(new ReasignarVisitaCommand(
            visitaId: $id,
            nuevoTecnicoId: $request->nuevo_tecnico_id,
            assignedBy: auth()->id(),
        ));

        return response()->json($dto);
    }
}
