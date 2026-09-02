<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Auditoria\Queries\GetRegistrosAuditoriaQuery;
use App\Application\Auditoria\QueryHandlers\GetRegistrosAuditoriaQueryHandler;
use App\Http\Controllers\Controller;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Solo lectura — sin store/update/destroy, la tabla es insert-only vía AuditoriaService. */
class RegistroAuditoriaController extends Controller
{
    public function __construct(private readonly GetRegistrosAuditoriaQueryHandler $getRegistrosHandler) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getRegistrosHandler->handle(new GetRegistrosAuditoriaQuery(
            pagination: $pagination,
            tipo_entidad: $request->get('tipo_entidad'),
            entidad_id: $request->get('entidad_id'),
            user_id: $request->get('user_id'),
            desde: $request->get('desde'),
            hasta: $request->get('hasta'),
        )));
    }
}
