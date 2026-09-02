<?php

namespace App\Http\Controllers\Api;

use App\Application\Servicios\Commands\CreateServicioCommand;
use App\Application\Servicios\Commands\DeleteServicioCommand;
use App\Application\Servicios\Commands\UpdateServicioCommand;
use App\Application\Servicios\Handlers\CreateServicioHandler;
use App\Application\Servicios\Handlers\DeleteServicioHandler;
use App\Application\Servicios\Handlers\UpdateServicioHandler;
use App\Application\Servicios\Queries\GetServicioByIdQuery;
use App\Application\Servicios\Queries\GetServicioBySlugQuery;
use App\Application\Servicios\Queries\GetServiciosQuery;
use App\Application\Servicios\QueryHandlers\GetServicioByIdQueryHandler;
use App\Application\Servicios\QueryHandlers\GetServicioBySlugQueryHandler;
use App\Application\Servicios\QueryHandlers\GetServiciosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\StoreServicioRequest;
use App\Http\Requests\Servicios\UpdateServicioRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function __construct(
        private readonly GetServiciosQueryHandler      $getServiciosHandler,
        private readonly GetServicioByIdQueryHandler   $getByIdHandler,
        private readonly GetServicioBySlugQueryHandler $getBySlugHandler,
        private readonly CreateServicioHandler         $createHandler,
        private readonly UpdateServicioHandler         $updateHandler,
        private readonly DeleteServicioHandler         $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray([
            'pageIndex' => $request->get('pageIndex', 1),
            'pageSize'  => $request->get('pageSize', 20),
            'query'     => $request->get('query', ''),
            'sortKey'   => $request->input('sort.key', 'orden'),
            'sortOrder' => $request->input('sort.order', 'asc'),
        ]);

        return response()->json(
            $this->getServiciosHandler->handle(
                new GetServiciosQuery(
                    $pagination,
                    $request->get('categoria') ?: null,
                    $request->boolean('soloDestacados', false),
                )
            )
        );
    }

    public function store(StoreServicioRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateServicioCommand(
            titulo:            $request->titulo,
            slug:              $request->slug,
            categoria:         $request->input('categoria', 'otro'),
            descripcion_corta: $request->descripcion_corta,
            descripcion:       $request->descripcion,
            icono:             $request->icono,
            imagen_url:        $request->imagen_url,
            imagen_alt:        $request->imagen_alt,
            whatsapp:          $request->whatsapp,
            precio_desde:      $request->filled('precio_desde') ? (float) $request->precio_desde : null,
            moneda:            $request->input('moneda', 'BOB'),
            modalidad:         $request->modalidad,
            destacado:         $request->boolean('destacado', false),
            orden:             $request->integer('orden', 0),
            estado:            $request->input('estado', 'publicado'),
            meta_titulo:       $request->meta_titulo,
            meta_descripcion:  $request->meta_descripcion,
        ));

        return response()->json($dto, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->getByIdHandler->handle(new GetServicioByIdQuery($id)));
    }

    public function showBySlug(string $slug): JsonResponse
    {
        return response()->json($this->getBySlugHandler->handle(new GetServicioBySlugQuery($slug)));
    }

    public function update(UpdateServicioRequest $request, int $id): JsonResponse
    {
        return response()->json($this->updateHandler->handle(new UpdateServicioCommand(
            id:                $id,
            titulo:            $request->titulo,
            slug:              $request->slug,
            categoria:         $request->categoria,
            descripcion_corta: $request->descripcion_corta,
            descripcion:       $request->descripcion,
            icono:             $request->icono,
            imagen_url:        $request->imagen_url,
            imagen_alt:        $request->imagen_alt,
            whatsapp:          $request->whatsapp,
            precio_desde:      $request->filled('precio_desde') ? (float) $request->precio_desde : null,
            moneda:            $request->moneda,
            modalidad:         $request->modalidad,
            destacado:         $request->has('destacado') ? $request->boolean('destacado') : null,
            orden:             $request->has('orden') ? $request->integer('orden') : null,
            estado:            $request->estado,
            meta_titulo:       $request->meta_titulo,
            meta_descripcion:  $request->meta_descripcion,
        )));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteServicioCommand($id));

        return response()->json(null, 204);
    }
}
